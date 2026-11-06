<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Stores images in public/media after validating and re-encoding them with GD.
 * Re-encoding drops EXIF/metadata and anything appended to the file, so an uploaded
 * "image" can never carry executable content. WebP variants feed responsive srcsets.
 */
class MediaService
{
    public const WIDTHS = [480, 960, 1600, 2400];

    public const MAX_PIXELS = 40_000_000;

    public const MAX_EDGE = 2560;

    public function storeImage(string $sourcePath, string $originalName, string $folder = 'general', ?string $alt = null, ?int $userId = null): Media
    {
        $info = @getimagesize($sourcePath);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($sourcePath);
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (! $info || ! in_array($mime, $allowed, true) || ! in_array($info['mime'], $allowed, true)) {
            throw new RuntimeException('Fișierul nu este o imagine JPEG, PNG sau WebP validă.');
        }
        [$w, $h] = $info;
        if ($w * $h > self::MAX_PIXELS) {
            throw new RuntimeException('Imaginea este prea mare ('.$w.'×'.$h.' px). Reduceți-o sub 40 megapixeli înainte de încărcare.');
        }
        $this->ensureMemory($w, $h);

        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/webp' => @imagecreatefromwebp($sourcePath),
        };
        if (! $src) {
            throw new RuntimeException('Imaginea nu a putut fi citită.');
        }
        if ($mime === 'image/jpeg') {
            $src = $this->applyExifOrientation($src, $sourcePath);
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $hasAlpha = $mime !== 'image/jpeg' && $this->hasAlpha($src);

        $dir = now()->format('Y/m');
        $base = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'imagine';
        $base = Str::limit($base, 60, '').'-'.Str::lower(Str::random(6));
        $abs = public_path('media/'.$dir);
        if (! is_dir($abs) && ! mkdir($abs, 0755, true) && ! is_dir($abs)) {
            throw new RuntimeException('Directorul media nu poate fi creat.');
        }

        // Original, capped at MAX_EDGE.
        $main = $this->resize($src, $w, $h, self::MAX_EDGE);
        $ext = $hasAlpha ? 'png' : 'jpg';
        if ($hasAlpha) {
            imagealphablending($main, false);
            imagesavealpha($main, true);
        }
        $mainRel = $dir.'/'.$base.'.'.$ext;
        $hasAlpha ? imagepng($main, public_path('media/'.$mainRel), 8) : imagejpeg($main, public_path('media/'.$mainRel), 84);
        $mw = imagesx($main);
        $mh = imagesy($main);

        $variants = [];
        if (function_exists('imagewebp')) {
            foreach (self::WIDTHS as $vw) {
                if ($vw > $mw && $vw !== self::WIDTHS[0]) {
                    continue;
                }
                $img = $this->resize($main, $mw, $mh, $vw, true);
                $rel = $dir.'/'.$base.'-'.imagesx($img).'.webp';
                if ($hasAlpha) {
                    imagealphablending($img, false);
                    imagesavealpha($img, true);
                }
                imagewebp($img, public_path('media/'.$rel), 80);
                $variants[] = ['format' => 'webp', 'width' => imagesx($img), 'height' => imagesy($img), 'path' => $rel];
                if ($img !== $main) {
                    imagedestroy($img);
                }
            }
        }
        if ($main !== $src) {
            imagedestroy($main);
        }
        imagedestroy($src);

        return Media::create([
            'path' => $mainRel,
            'original_name' => Str::limit($originalName, 250, ''),
            'mime' => $hasAlpha ? 'image/png' : 'image/jpeg',
            'size' => filesize(public_path('media/'.$mainRel)),
            'width' => $mw,
            'height' => $mh,
            'alt' => $alt,
            'folder' => $folder,
            'variants' => $variants,
            'created_by' => $userId,
        ]);
    }

    public function delete(Media $media): void
    {
        $paths = array_merge([$media->path], array_column($media->variants ?? [], 'path'));
        foreach ($paths as $p) {
            $full = realpath(public_path('media/'.$p));
            $root = realpath(public_path('media'));
            if ($full && $root && str_starts_with($full, $root.DIRECTORY_SEPARATOR) && is_file($full)) {
                @unlink($full);
            }
        }
        $media->delete();
    }

    /** Regenerate variants for an existing media item (used by "Optimizează"). */
    public function regenerate(Media $media): Media
    {
        $file = public_path('media/'.$media->path);
        if (! is_file($file)) {
            throw new RuntimeException('Fișierul original lipsește.');
        }
        $tmp = tempnam(sys_get_temp_dir(), 'elg');
        copy($file, $tmp);
        $new = $this->storeImage($tmp, $media->original_name, $media->folder, $media->alt, $media->created_by);
        @unlink($tmp);
        $old = $media->replicate();
        $old->id = $media->id;
        $media->fill($new->only(['path', 'mime', 'size', 'width', 'height', 'variants']))->save();
        $new->delete();
        foreach (array_merge([$old->path], array_column($old->variants ?? [], 'path')) as $p) {
            if ($p !== $media->path && ! in_array($p, array_column($media->variants ?? [], 'path'), true)) {
                @unlink(public_path('media/'.$p));
            }
        }

        return $media->fresh();
    }

    private function resize($img, int $w, int $h, int $maxW, bool $byWidth = false)
    {
        $scale = $byWidth ? min(1, $maxW / $w) : min(1, $maxW / max($w, $h));
        if ($scale >= 1) {
            return $img;
        }
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $dst;
    }

    private function hasAlpha($img): bool
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $step = max(1, (int) (max($w, $h) / 60));
        for ($x = 0; $x < $w; $x += $step) {
            for ($y = 0; $y < $h; $y += $step) {
                if (((imagecolorat($img, $x, $y) >> 24) & 0x7F) > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    private function applyExifOrientation($img, string $path)
    {
        if (! function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data($path);
        $o = $exif['Orientation'] ?? 1;

        return match ((int) $o) {
            3 => imagerotate($img, 180, 0),
            6 => imagerotate($img, -90, 0),
            8 => imagerotate($img, 90, 0),
            default => $img,
        };
    }

    private function ensureMemory(int $w, int $h): void
    {
        $needed = (int) ($w * $h * 5 * 2.2) + 32 * 1024 * 1024;
        $limit = $this->bytes((string) ini_get('memory_limit'));
        if ($limit > 0 && $needed > $limit) {
            @ini_set('memory_limit', (string) max($needed, $limit));
            $limit = $this->bytes((string) ini_get('memory_limit'));
            if ($limit > 0 && $needed > $limit) {
                throw new RuntimeException('Memoria PHP este insuficientă pentru această imagine. Încărcați o variantă mai mică (max. 4000 px).');
            }
        }
    }

    private function bytes(string $v): int
    {
        $v = trim($v);
        if ($v === '-1') {
            return -1;
        }
        $n = (int) $v;

        return match (strtolower(substr($v, -1))) {
            'g' => $n * 1024 ** 3,
            'm' => $n * 1024 ** 2,
            'k' => $n * 1024,
            default => $n,
        };
    }
}
