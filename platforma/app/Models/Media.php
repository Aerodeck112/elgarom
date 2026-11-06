<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['variants' => 'array', 'alt_i18n' => 'array'];

    /** Alternative text in the current language (falls back to the Romanian text). */
    public function altText(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return ($this->alt_i18n[$locale] ?? null) ?: $this->alt;
    }

    /** Public URL of the original (re-encoded) file. */
    public function url(): string
    {
        return asset('media/'.$this->path);
    }

    /** srcset built from generated WebP variants. */
    public function srcset(string $format = 'webp'): string
    {
        $parts = [];
        foreach ($this->variants ?? [] as $v) {
            if (($v['format'] ?? '') === $format) {
                $parts[] = asset('media/'.$v['path']).' '.$v['width'].'w';
            }
        }

        return implode(', ', $parts);
    }

    public function variantUrl(int $minWidth, string $format = 'webp'): string
    {
        $best = null;
        foreach ($this->variants ?? [] as $v) {
            if (($v['format'] ?? '') !== $format) {
                continue;
            }
            if ($v['width'] >= $minWidth && ($best === null || $v['width'] < $best['width'])) {
                $best = $v;
            }
        }

        return $best ? asset('media/'.$best['path']) : $this->url();
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }

    public function ratio(): string
    {
        return ($this->width && $this->height) ? $this->width.' / '.$this->height : '3 / 2';
    }
}
