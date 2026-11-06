<?php

namespace App\Services;

use App\Models\Backup;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Backups without shell access: a pure-PHP SQL dump (one statement per line) plus media and
 * private files, packed with a manifest of SHA-256 hashes. Stored in storage/app/private/backups,
 * which is outside the web root and additionally denied by .htaccess.
 */
class BackupService
{
    /** Tables never dumped or restored (volatile runtime state). */
    public const SKIP_TABLES = ['sessions', 'cache', 'cache_locks'];

    public static function dir(): string
    {
        $d = storage_path('app/private/backups');
        if (! is_dir($d)) {
            mkdir($d, 0700, true);
        }
        if (! is_file($d.'/.htaccess')) {
            file_put_contents($d.'/.htaccess', "Require all denied\n");
        }

        return $d;
    }

    public function create(string $type = 'complet', ?int $userId = null, string $notes = '', array $extraFiles = []): Backup
    {
        @set_time_limit(0);
        $name = 'elgarom-'.$type.'-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.zip';
        $backup = Backup::create(['filename' => $name, 'type' => $type, 'status' => 'in_lucru', 'app_version' => config('elgarom.version'), 'created_by' => $userId, 'notes' => $notes]);
        $path = self::dir().'/'.$name;
        try {
            $sqlFile = tempnam(sys_get_temp_dir(), 'sql');
            $tables = $this->dumpDatabase($sqlFile);
            $zip = new ZipArchive;
            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
                throw new RuntimeException('Arhiva nu poate fi creată.');
            }
            $zip->addFile($sqlFile, 'database.sql');
            $files = ['database.sql' => hash_file('sha256', $sqlFile)];
            $roots = $type === 'pre_actualizare' ? [] : ['public/media', 'storage/app/private'];
            foreach ($roots as $root) {
                foreach ($this->walk(base_path($root)) as $abs) {
                    $rel = $root.'/'.ltrim(substr($abs, strlen(base_path($root))), '/');
                    if (str_starts_with($rel, 'storage/app/private/backups/') || str_starts_with($rel, 'storage/app/private/updates/') || str_starts_with($rel, 'storage/app/private/tmp/')) {
                        continue;
                    }
                    $zip->addFile($abs, 'files/'.$rel);
                    $files['files/'.$rel] = hash_file('sha256', $abs);
                }
            }
            foreach ($extraFiles as $rel) {
                $abs = base_path($rel);
                if (is_file($abs)) {
                    $zip->addFile($abs, 'code/'.$rel);
                    $files['code/'.$rel] = hash_file('sha256', $abs);
                }
            }
            $manifest = [
                'format' => 1, 'app' => 'elgarom', 'type' => $type, 'app_version' => config('elgarom.version'),
                'created_at' => now()->toIso8601String(), 'tables' => $tables, 'files' => $files,
                // Needed to re-encrypt secrets if restored on an installation with a different APP_KEY.
                'app_key' => config('app.key'),
            ];
            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $zip->setArchiveComment('ELGAROM backup '.$type.' '.now()->toIso8601String());
            if (! $zip->close()) {
                throw new RuntimeException('Arhiva nu a putut fi finalizată (spațiu pe disc?).');
            }
            @unlink($sqlFile);
            @chmod($path, 0600);
            $backup->update(['status' => 'ok', 'size' => filesize($path), 'sha256' => hash_file('sha256', $path)]);
            $this->verify($backup);
        } catch (\Throwable $e) {
            @unlink($path);
            isset($sqlFile) && @unlink($sqlFile);
            $backup->update(['status' => 'esuat', 'notes' => trim($notes.' '.$e->getMessage())]);
            throw $e;
        }

        return $backup->fresh();
    }

    /** Re-opens the archive and checks every hash. Marks the backup verified. */
    public function verify(Backup $backup): bool
    {
        $path = $backup->absolutePath();
        if (! is_file($path) || ($backup->sha256 && hash_file('sha256', $path) !== $backup->sha256)) {
            throw new RuntimeException('Fișierul de backup lipsește sau a fost modificat.');
        }
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CHECKCONS) !== true) {
            throw new RuntimeException('Arhiva de backup este coruptă.');
        }
        $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
        if (! is_array($manifest) || ($manifest['app'] ?? '') !== 'elgarom') {
            throw new RuntimeException('Arhiva nu este un backup ELGAROM.');
        }
        foreach ($manifest['files'] as $name => $hash) {
            $stream = $zip->getStream($name);
            if (! $stream) {
                throw new RuntimeException('Lipsește din backup: '.$name);
            }
            $ctx = hash_init('sha256');
            hash_update_stream($ctx, $stream);
            fclose($stream);
            if (hash_final($ctx) !== $hash) {
                throw new RuntimeException('Hash incorect în backup: '.$name);
            }
        }
        $sql = (string) $zip->getFromName('database.sql');
        if (! str_contains($sql, 'CREATE TABLE') || ! str_contains($sql, '-- ELGAROM dump end')) {
            throw new RuntimeException('Dump-ul bazei de date este incomplet.');
        }
        $zip->close();
        $backup->update(['verified_at' => now()]);

        return true;
    }

    private function dumpDatabase(string $file): array
    {
        $pdo = DB::connection()->getPdo();
        $fh = fopen($file, 'w');
        fwrite($fh, "-- ELGAROM dump ".now()->toIso8601String()."\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
        $tables = [];
        foreach (DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"') as $row) {
            $t = array_values((array) $row)[0];
            if (in_array($t, self::SKIP_TABLES, true)) {
                // Structure only, without data: an import into an empty database (phpMyAdmin) still gets
                // the session and cache tables, and a restore keeps the current sessions.
                $create = array_values((array) DB::selectOne('SHOW CREATE TABLE `'.$t.'`'))[1];
                fwrite($fh, preg_replace('/^CREATE TABLE/', 'CREATE TABLE IF NOT EXISTS', str_replace(["\r", "\n"], ' ', $create)).";\n");

                continue;
            }
            $tables[] = $t;
            $create = array_values((array) DB::selectOne('SHOW CREATE TABLE `'.$t.'`'))[1];
            fwrite($fh, 'DROP TABLE IF EXISTS `'.$t."`;\n".str_replace(["\r", "\n"], ' ', $create).";\n");
            $cols = null;
            $stmt = $pdo->query('SELECT * FROM `'.$t.'`', \PDO::FETCH_ASSOC);
            $batch = [];
            while ($r = $stmt->fetch()) {
                $cols ??= '(`'.implode('`,`', array_keys($r)).'`)';
                $batch[] = '('.implode(',', array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $r)).')';
                if (count($batch) >= 200) {
                    fwrite($fh, 'INSERT INTO `'.$t.'` '.$cols.' VALUES '.implode(',', $batch).";\n");
                    $batch = [];
                }
            }
            if ($batch) {
                fwrite($fh, 'INSERT INTO `'.$t.'` '.$cols.' VALUES '.implode(',', $batch).";\n");
            }
        }
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n-- ELGAROM dump end\n");
        fclose($fh);

        return $tables;
    }

    /** Executes a dump produced by dumpDatabase (one statement per line). */
    public function importSql(string $sql): void
    {
        $pdo = DB::connection()->getPdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (preg_split('/;\r?\n/', $sql) as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '' || str_starts_with($stmt, '--')) {
                continue;
            }
            $pdo->exec($stmt);
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Full restore: safety backup of the current DB first, then database and files from the archive.
     * Secrets encrypted with a different APP_KEY are re-encrypted with the current key.
     */
    public function restore(Backup $backup, ?int $userId = null): Backup
    {
        @set_time_limit(0);
        $this->verify($backup);
        $safety = $this->create('pre_restaurare', $userId, 'Creat automat înainte de restaurarea '.$backup->filename);
        $zip = new ZipArchive;
        $zip->open($backup->absolutePath());
        $manifest = json_decode($zip->getFromName('manifest.json'), true);
        Artisan::call('down', ['--retry' => 30]);
        try {
            $this->importSql($zip->getFromName('database.sql'));
            foreach (array_keys($manifest['files']) as $name) {
                if (! str_starts_with($name, 'files/')) {
                    continue;
                }
                $rel = substr($name, 6);
                if (! $this->safeRelative($rel) || ! (str_starts_with($rel, 'public/media/') || str_starts_with($rel, 'storage/app/private/'))) {
                    continue;
                }
                $dest = base_path($rel);
                if (! is_dir(dirname($dest))) {
                    mkdir(dirname($dest), 0755, true);
                }
                copy('zip://'.$backup->absolutePath().'#'.$name, $dest);
            }
            if (! empty($manifest['app_key']) && $manifest['app_key'] !== config('app.key')) {
                $this->reencrypt($manifest['app_key']);
            }
            Artisan::call('migrate', ['--force' => true]);
            \App\Models\Setting::flush();
            Artisan::call('optimize:clear');
        } finally {
            $zip->close();
            Artisan::call('up');
        }
        // the backups table itself was restored: make sure both archives are listed
        foreach ([$backup, $safety] as $b) {
            Backup::firstOrCreate(['filename' => $b->filename], $b->only(['type', 'size', 'sha256', 'status', 'app_version', 'verified_at', 'notes']));
        }

        return $safety;
    }

    private function reencrypt(string $oldKey): void
    {
        $key = str_starts_with($oldKey, 'base64:') ? base64_decode(substr($oldKey, 7)) : $oldKey;
        $old = new Encrypter($key, config('app.cipher'));
        foreach (DB::table('settings')->where('is_encrypted', true)->get() as $row) {
            try {
                DB::table('settings')->where('id', $row->id)->update(['value' => \Illuminate\Support\Facades\Crypt::encryptString($old->decryptString($row->value))]);
            } catch (\Throwable) {
                DB::table('settings')->where('id', $row->id)->delete();
            }
        }
        foreach (DB::table('users')->get() as $u) {
            $upd = [];
            foreach (['two_factor_secret', 'two_factor_recovery_codes'] as $c) {
                if ($u->{$c}) {
                    try {
                        $upd[$c] = \Illuminate\Support\Facades\Crypt::encryptString($old->decryptString($u->{$c}));
                    } catch (\Throwable) {
                        $upd[$c] = null;
                        $upd['two_factor_confirmed_at'] = null;
                    }
                }
            }
            $upd && DB::table('users')->where('id', $u->id)->update($upd);
        }
    }

    public function safeRelative(string $rel): bool
    {
        return $rel !== '' && ! str_contains($rel, "\0") && ! str_contains($rel, '\\') && ! str_starts_with($rel, '/')
            && ! preg_match('#(^|/)\.\.(/|$)#', $rel) && ! preg_match('/^[a-zA-Z]:/', $rel);
    }

    private function walk(string $dir): \Generator
    {
        if (! is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && ! $f->isLink()) {
                yield $f->getPathname();
            }
        }
    }

    public function prune(int $keep = 10): void
    {
        foreach (Backup::where('type', 'complet')->latest()->skip($keep)->take(100)->get() as $b) {
            @unlink($b->absolutePath());
            $b->delete();
        }
    }
}
