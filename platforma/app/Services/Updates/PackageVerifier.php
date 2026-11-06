<?php

namespace App\Services\Updates;

use RuntimeException;
use ZipArchive;

/**
 * Verifies a signed update package.
 *
 * Layout:
 *   manifest.json   – {"format":1,"app":"elgarom","key_id":"…","version":"1.1.0","min_from":"1.0.0",
 *                      "php_min":"8.3.0","extensions":[…],"changelog":"…","files":{"path":"sha256"},"delete":[…]}
 *   manifest.sig    – base64 Ed25519 detached signature of the exact manifest.json bytes
 *   files/<path>    – payload, one entry per manifest file
 *
 * Nothing in the archive is trusted until the signature verifies against a public key
 * shipped with the application (config/elgarom.php). The private key never leaves the
 * release machine.
 */
class PackageVerifier
{
    public const MAX_RATIO = 250;

    public function verify(string $zipPath, string $currentVersion): array
    {
        $cfg = config('elgarom.updates');
        if (! is_file($zipPath) || filesize($zipPath) > $cfg['max_package_bytes']) {
            throw new RuntimeException('Pachetul lipsește sau depășește dimensiunea maximă admisă.');
        }
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CHECKCONS) !== true) {
            throw new RuntimeException('Fișierul nu este o arhivă ZIP validă.');
        }
        try {
            if ($zip->numFiles > $cfg['max_entries']) {
                throw new RuntimeException('Arhiva conține prea multe fișiere.');
            }
            $total = 0;
            $entries = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $st = $zip->statIndex($i);
                $name = $st['name'];
                $this->assertSafeName($name);
                if (str_ends_with($name, '/')) {
                    continue; // directory entries are ignored
                }
                $zip->getExternalAttributesIndex($i, $os, $attr);
                if ($os === ZipArchive::OPSYS_UNIX && ((($attr >> 16) & 0170000) === 0120000)) {
                    throw new RuntimeException('Arhiva conține un link simbolic: '.$name);
                }
                $total += $st['size'];
                if ($total > $cfg['max_uncompressed_bytes']) {
                    throw new RuntimeException('Conținutul dezarhivat ar depăși limita admisă.');
                }
                if ($st['comp_size'] > 0 && $st['size'] / $st['comp_size'] > self::MAX_RATIO && $st['size'] > 1048576) {
                    throw new RuntimeException('Raport de compresie suspect (posibilă arhivă-bombă): '.$name);
                }
                if ($name !== 'manifest.json' && $name !== 'manifest.sig' && ! str_starts_with($name, 'files/')) {
                    throw new RuntimeException('Fișier neașteptat în arhivă: '.$name);
                }
                $entries[$name] = $st['size'];
            }
            $manifestRaw = $zip->getFromName('manifest.json');
            $sig = $zip->getFromName('manifest.sig');
            if ($manifestRaw === false || $sig === false) {
                throw new RuntimeException('Pachetul nu este semnat (lipsește manifest.json sau manifest.sig).');
            }
            $keyId = $this->verifySignature($manifestRaw, trim($sig));
            $m = json_decode($manifestRaw, true, 64, JSON_THROW_ON_ERROR);

            if (($m['format'] ?? null) !== 1 || ($m['app'] ?? null) !== 'elgarom') {
                throw new RuntimeException('Pachetul nu este destinat acestei aplicații.');
            }
            if (($m['key_id'] ?? null) !== $keyId) {
                throw new RuntimeException('Identificatorul cheii din manifest nu corespunde semnăturii.');
            }
            foreach (['version', 'min_from', 'files'] as $req) {
                if (empty($m[$req])) {
                    throw new RuntimeException('Manifest incomplet: lipsește „'.$req.'”.');
                }
            }
            if (! $this->semver($m['version']) || ! $this->semver($m['min_from'])) {
                throw new RuntimeException('Versiune invalidă în manifest.');
            }
            if (version_compare($m['version'], $currentVersion, '<=')) {
                throw new RuntimeException('Versiunea pachetului ('.$m['version'].') nu este mai nouă decât cea instalată ('.$currentVersion.').');
            }
            if (version_compare($currentVersion, $m['min_from'], '<')) {
                throw new RuntimeException('Pachetul necesită cel puțin versiunea '.$m['min_from'].'; instalată: '.$currentVersion.'. Instalați întâi actualizările intermediare.');
            }
            if (! empty($m['max_from']) && version_compare($currentVersion, $m['max_from'], '>')) {
                throw new RuntimeException('Pachetul nu se poate aplica peste versiunea '.$currentVersion.'.');
            }
            if (! empty($m['php_min']) && version_compare(PHP_VERSION, $m['php_min'], '<')) {
                throw new RuntimeException('Pachetul necesită PHP '.$m['php_min'].'; serverul are '.PHP_VERSION.'.');
            }
            foreach ((array) ($m['extensions'] ?? []) as $ext) {
                if (! extension_loaded($ext)) {
                    throw new RuntimeException('Lipsește extensia PHP necesară: '.$ext);
                }
            }

            // Every payload file must be declared, every declared file must be present, hashes must match.
            $declared = [];
            foreach ($m['files'] as $path => $hash) {
                $this->assertSafeName($path);
                $this->assertNotProtected($path);
                if (! preg_match('/^[a-f0-9]{64}$/', (string) $hash)) {
                    throw new RuntimeException('Hash invalid în manifest pentru '.$path);
                }
                if (! isset($entries['files/'.$path])) {
                    throw new RuntimeException('Fișier declarat dar absent din pachet: '.$path);
                }
                $stream = $zip->getStream('files/'.$path);
                $ctx = hash_init('sha256');
                hash_update_stream($ctx, $stream);
                fclose($stream);
                if (! hash_equals($hash, hash_final($ctx))) {
                    throw new RuntimeException('Fișier modificat după semnare: '.$path);
                }
                $declared['files/'.$path] = true;
            }
            foreach (array_keys($entries) as $name) {
                if (str_starts_with($name, 'files/') && ! isset($declared[$name])) {
                    throw new RuntimeException('Fișier nedeclarat în manifest: '.$name);
                }
            }
            foreach ((array) ($m['delete'] ?? []) as $path) {
                $this->assertSafeName($path);
                $this->assertNotProtected($path);
            }
            $m['_key_id'] = $keyId;
            $m['_sha256'] = hash_file('sha256', $zipPath);

            return $m;
        } finally {
            $zip->close();
        }
    }

    private function verifySignature(string $manifest, string $sigB64): string
    {
        $sig = base64_decode($sigB64, true);
        if ($sig === false || strlen($sig) !== SODIUM_CRYPTO_SIGN_BYTES) {
            throw new RuntimeException('Semnătura pachetului are un format invalid.');
        }
        foreach (config('elgarom.updates.public_keys') as $id => $pk) {
            $key = base64_decode($pk, true);
            if ($key && strlen($key) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES && sodium_crypto_sign_verify_detached($sig, $manifest, $key)) {
                return $id;
            }
        }
        throw new RuntimeException('Semnătura nu este validă: pachetul nu provine de la emitentul de încredere sau a fost modificat.');
    }

    public function assertSafeName(string $name): void
    {
        if ($name === '' || strlen($name) > 400 || str_contains($name, "\0") || str_contains($name, '\\') || str_starts_with($name, '/')
            || preg_match('#(^|/)\.\.?(/|$)#', $name) || preg_match('/^[a-zA-Z]:/', $name) || preg_match('/[\x00-\x1F\x7F]/', $name)) {
            throw new RuntimeException('Cale nepermisă în pachet: '.substr($name, 0, 120));
        }
    }

    public function assertNotProtected(string $path): void
    {
        foreach (config('elgarom.updates.protected_paths') as $p) {
            if ($path === rtrim($p, '/') || (str_ends_with($p, '/') && str_starts_with($path, $p))) {
                throw new RuntimeException('Pachetul încearcă să modifice o cale protejată: '.$path);
            }
        }
        if (preg_match('#(^|/)\.env(\.|$)#', $path) || str_starts_with($path, 'public/media')) {
            throw new RuntimeException('Pachetul încearcă să modifice o cale protejată: '.$path);
        }
    }

    private function semver(string $v): bool
    {
        return (bool) preg_match('/^\d+\.\d+\.\d+$/', $v);
    }
}
