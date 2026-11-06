<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * Content checks for client uploads. The extension whitelist alone is not trusted:
 * the file content must match its type and must not look like a script or executable.
 * Files are stored outside the web root under random names and are never executed.
 */
class UploadInspector
{
    private const DANGEROUS_MIMES = [
        'text/x-php', 'application/x-php', 'application/x-httpd-php', 'text/html', 'application/xhtml+xml',
        'application/javascript', 'text/javascript', 'application/x-executable', 'application/x-dosexec',
        'application/x-sharedlib', 'application/x-mach-binary', 'application/x-msdownload', 'application/x-sh',
        'text/x-shellscript', 'image/svg+xml', 'application/java-archive',
    ];

    public function problem(UploadedFile $file, array $allowedExt): ?string
    {
        if (! $file->isValid()) {
            return 'Fișierul „'.$this->name($file).'” nu a fost încărcat complet.';
        }
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, $allowedExt, true)) {
            return 'Tipul fișierului „'.$this->name($file).'” nu este acceptat.';
        }
        $path = $file->getRealPath();
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
        if (in_array($mime, self::DANGEROUS_MIMES, true)) {
            return 'Fișierul „'.$this->name($file).'” nu este acceptat.';
        }
        $head = (string) file_get_contents($path, false, null, 0, 2048);
        if (preg_match('/<\?php|<script|<\?=|#!\/(usr|bin)/i', $head)) {
            return 'Fișierul „'.$this->name($file).'” nu este acceptat.';
        }
        $ok = match ($ext) {
            'pdf' => str_starts_with($head, '%PDF-'),
            'jpg', 'jpeg' => $mime === 'image/jpeg' && @getimagesize($path) !== false,
            'png' => $mime === 'image/png' && @getimagesize($path) !== false,
            'webp' => $mime === 'image/webp' && @getimagesize($path) !== false,
            'dwg' => (bool) preg_match('/^AC10\d\d/', $head),
            'step', 'stp' => str_contains($head, 'ISO-10303-21'),
            'igs', 'iges' => strlen($head) > 72,
            'dxf' => (bool) preg_match('/^\s*0\s*\r?\n\s*SECTION/i', $head) || str_starts_with($head, 'AutoCAD Binary DXF'),
            default => false,
        };

        return $ok ? null : 'Conținutul fișierului „'.$this->name($file).'” nu corespunde tipului '.strtoupper($ext).'.';
    }

    public function name(UploadedFile $file): string
    {
        return mb_substr(preg_replace('/[^\pL\pN ._\-()]+/u', '_', $file->getClientOriginalName()), 0, 120);
    }

    public function detectedMime(string $path): string
    {
        return (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
    }
}
