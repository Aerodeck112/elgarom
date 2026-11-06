<?php

namespace App\Services\Updates;

use App\Models\Backup;
use App\Models\SystemUpdate;
use App\Services\BackupService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Applies a verified package:
 *  1. exclusive lock (one installation at a time)
 *  2. backup of the database and of every file the package will overwrite or delete; backup verified
 *  3. extraction to a staging directory, hashes re-checked, PHP syntax checked
 *  4. maintenance mode, atomic per-file replacement, deletions
 *  5. tracked migrations (Laravel migrations table)
 *  6. health checks (HTTP /health with token + in-process DB check)
 *  7. on any failure: files and database restored from step 2, then maintenance mode is lifted
 */
class Installer
{
    private array $log = [];

    private ?SystemUpdate $record = null;

    public function __construct(private PackageVerifier $verifier, private BackupService $backups) {}

    public static function workDir(): string
    {
        $d = storage_path('app/private/updates');
        if (! is_dir($d)) {
            mkdir($d, 0700, true);
            file_put_contents($d.'/.htaccess', "Require all denied\n");
        }

        return $d;
    }

    private function log(string $msg): void
    {
        $this->log[] = '['.now()->format('H:i:s').'] '.$msg;
        $this->record?->update(['log' => implode("\n", $this->log)]);
    }

    public function install(string $zipPath, ?int $userId): SystemUpdate
    {
        @set_time_limit(0);
        @ignore_user_abort(true);
        $lockFile = self::workDir().'/install.lock';
        $lock = fopen($lockFile, 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException('O altă actualizare este în curs. Așteptați finalizarea ei.');
        }
        if (SystemUpdate::where('status', 'in_curs')->where('started_at', '>', now()->subMinutes(30))->exists()) {
            flock($lock, LOCK_UN);
            throw new RuntimeException('O altă actualizare figurează ca fiind în curs.');
        }

        $from = config('elgarom.version');
        $manifest = $this->verifier->verify($zipPath, $from); // verify again right before installing
        $this->record = SystemUpdate::create([
            'version_from' => $from, 'version_to' => $manifest['version'], 'package_sha256' => $manifest['_sha256'],
            'status' => 'in_curs', 'changelog' => $manifest['changelog'] ?? null, 'user_id' => $userId, 'started_at' => now(),
        ]);
        $this->log('Pachet verificat: semnătură '.$manifest['_key_id'].', '.count($manifest['files']).' fișiere, '.count($manifest['delete'] ?? []).' ștergeri.');

        $staging = self::workDir().'/staging-'.$this->record->id;
        $applied = false;
        $added = [];
        $backup = null;
        try {
            // 2. backup
            $touched = array_values(array_unique(array_merge(array_keys($manifest['files']), (array) ($manifest['delete'] ?? []))));
            $existing = array_values(array_filter($touched, fn ($p) => is_file(base_path($p))));
            $backup = $this->backups->create('pre_actualizare', $userId, 'Înainte de '.$from.' → '.$manifest['version'], $existing);
            $this->record->update(['backup_id' => $backup->id]);
            $this->log('Backup verificat: '.$backup->filename.' ('.count($existing).' fișiere de cod + baza de date).');

            // 3. staging
            $this->extract($zipPath, $staging, $manifest);
            $this->log('Pachet dezarhivat în staging; hash-uri și sintaxă PHP verificate.');

            // 4. apply
            Artisan::call('down', ['--retry' => 60]);
            $this->log('Mod mentenanță activ.');
            foreach ($manifest['files'] as $rel => $hash) {
                $dest = base_path($rel);
                if (! is_file($dest)) {
                    $added[] = $rel;
                }
                if (! is_dir(dirname($dest)) && ! mkdir(dirname($dest), 0755, true) && ! is_dir(dirname($dest))) {
                    throw new RuntimeException('Nu se poate crea directorul pentru '.$rel);
                }
                $tmp = dirname($dest).'/.upd-'.Str::random(8);
                if (! copy($staging.'/'.$rel, $tmp) || ! rename($tmp, $dest)) {
                    @unlink($tmp);
                    throw new RuntimeException('Nu se poate scrie '.$rel.' (permisiuni?).');
                }
                $applied = true;
            }
            foreach ((array) ($manifest['delete'] ?? []) as $rel) {
                if (is_file(base_path($rel))) {
                    unlink(base_path($rel));
                    $applied = true;
                }
            }
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
            $this->log('Fișiere aplicate.');

            // 5. migrations
            $code = Artisan::call('migrate', ['--force' => true]);
            $this->log('Migrații: '.trim(Artisan::output()));
            if ($code !== 0) {
                throw new RuntimeException('Migrațiile au eșuat.');
            }
            @unlink(base_path('bootstrap/cache/config.php'));
            @unlink(base_path('bootstrap/cache/routes-v7.php'));
            Artisan::call('view:clear');

            // 6. health
            $this->healthCheck($manifest['version']);
            Artisan::call('up');
            $this->record->update(['status' => 'instalat', 'finished_at' => now()]);
            $this->log('Actualizare instalată: '.$from.' → '.$manifest['version'].'.');
        } catch (\Throwable $e) {
            $this->log('EROARE: '.$e->getMessage());
            if ($applied || $backup) {
                $this->rollback($backup, $added, $applied);
            }
            try {
                Artisan::call('up');
            } catch (\Throwable) {
                @unlink(storage_path('framework/down'));
            }
            $this->record->update(['status' => $this->record->status === 'esuat_critic' ? 'esuat_critic' : 'esuat_restaurat', 'finished_at' => now()]);
            throw new RuntimeException('Actualizarea a eșuat: '.$e->getMessage().' '.($this->record->status === 'esuat_critic' ? 'Restaurarea automată NU a reușit – urmați procedura din docs/ACTUALIZARI.md.' : 'Fișierele și baza de date au fost restaurate.'), 0, $e);
        } finally {
            $this->rrmdir($staging);
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        return $this->record->fresh();
    }

    private function extract(string $zipPath, string $staging, array $manifest): void
    {
        $this->rrmdir($staging);
        mkdir($staging, 0700, true);
        $zip = new ZipArchive;
        $zip->open($zipPath);
        foreach ($manifest['files'] as $rel => $hash) {
            $dest = $staging.'/'.$rel;
            if (! is_dir(dirname($dest))) {
                mkdir(dirname($dest), 0700, true);
            }
            $in = $zip->getStream('files/'.$rel);
            $out = fopen($dest, 'w');
            stream_copy_to_stream($in, $out);
            fclose($in);
            fclose($out);
            if (! hash_equals($hash, hash_file('sha256', $dest))) {
                throw new RuntimeException('Hash diferit după dezarhivare: '.$rel);
            }
            if (str_ends_with($rel, '.php')) {
                try {
                    token_get_all(file_get_contents($dest), TOKEN_PARSE);
                } catch (\ParseError $pe) {
                    throw new RuntimeException('Eroare de sintaxă PHP în '.$rel.': '.$pe->getMessage());
                }
            }
        }
        $zip->close();
    }

    private function healthCheck(string $expected): void
    {
        DB::select('select 1');
        $tokenFile = storage_path('app/private/health.token');
        if (! is_file($tokenFile)) {
            file_put_contents($tokenFile, Str::random(48));
        }
        $token = trim(file_get_contents($tokenFile));
        // Maintenance mode blocks normal requests; the health route is exercised right after "up".
        Artisan::call('up');
        try {
            $r = Http::timeout(15)->get(route('health', ['token' => $token]));
            if ($r->successful()) {
                $j = $r->json();
                if (! ($j['ok'] ?? false) || ($j['version'] ?? null) !== $expected) {
                    throw new RuntimeException('Verificarea de sănătate a eșuat: '.json_encode($j));
                }
                $this->log('Verificare HTTP /health: OK, versiune '.$expected.'.');

                return;
            }
            throw new RuntimeException('Verificarea de sănătate a răspuns cu HTTP '.$r->status().'.');
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Some hosts block loopback HTTP. Fall back to in-process checks.
            $this->log('HTTP /health inaccesibil din server ('.$e->getMessage().'); verificare internă.');
            if (! \App\Models\Page::where('status', 'published')->exists()) {
                throw new RuntimeException('Baza de date nu conține pagini publicate după actualizare.');
            }
        } finally {
            Artisan::call('down', ['--retry' => 60]);
        }
    }

    private function rollback(?Backup $backup, array $added, bool $filesTouched): void
    {
        $this->log('Restaurare automată pornită.');
        try {
            if (! $backup) {
                $this->log('Nu există backup (eșec înainte de modificări): nimic de restaurat.');

                return;
            }
            $zip = new ZipArchive;
            if ($zip->open($backup->absolutePath()) !== true) {
                throw new RuntimeException('Backup-ul nu poate fi deschis.');
            }
            $manifest = json_decode($zip->getFromName('manifest.json'), true);
            if ($filesTouched) {
                foreach (array_keys($manifest['files']) as $name) {
                    if (str_starts_with($name, 'code/')) {
                        $rel = substr($name, 5);
                        file_put_contents(base_path($rel), $zip->getFromName($name));
                    }
                }
                foreach ($added as $rel) {
                    @unlink(base_path($rel));
                }
                $this->log('Fișiere restaurate ('.count($added).' fișiere noi eliminate).');
            }
            // Tables created by new migrations are not in the dump: drop them so the schema matches the backup.
            foreach (DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"') as $row) {
                $t = array_values((array) $row)[0];
                if (! in_array($t, $manifest['tables'] ?? [], true) && ! in_array($t, BackupService::SKIP_TABLES, true)) {
                    DB::statement('DROP TABLE IF EXISTS `'.$t.'`');
                }
            }
            $this->backups->importSql($zip->getFromName('database.sql'));
            $zip->close();
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
            $this->log('Baza de date restaurată din backup.');
        } catch (\Throwable $e) {
            $this->log('RESTAURARE EȘUATĂ: '.$e->getMessage());
            $this->record?->update(['status' => 'esuat_critic']);
        }
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) {
            $f->isDir() && ! $f->isLink() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($dir);
    }
}
