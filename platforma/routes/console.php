<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Ai\Assistant;
use App\Services\BackupService;
use App\Services\Mail\Outbox;
use App\Services\Updates\Installer;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('elgarom:mail', function () {
    [$sent, $failed] = app(Outbox::class)->process(50);
    $this->info("Emailuri trimise: $sent, eșuate: $failed");
})->purpose('Trimite emailurile din coadă (cu reîncercare)');

Artisan::command('elgarom:prune', function () {
    $ai = Assistant::prune();
    $tmp = 0;
    foreach (glob(storage_path('app/private/tmp/*'), GLOB_ONLYDIR) ?: [] as $dir) {
        if (filemtime($dir) < time() - 86400) {
            array_map('unlink', glob($dir.'/*') ?: []);
            @rmdir($dir);
            $tmp++;
        }
    }
    foreach (glob(Installer::workDir().'/pending-*.zip') ?: [] as $f) {
        if (filemtime($f) < time() - 86400) {
            @unlink($f);
        }
    }
    \App\Models\AuditLog::where('created_at', '<', now()->subDays((int) setting('security.audit_days', 365)))->delete();
    $this->info("Conversații AI șterse: $ai, directoare temporare: $tmp");
})->purpose('Aplică retenția (AI, fișiere temporare, jurnal)');

Artisan::command('elgarom:backup {--type=complet}', function (BackupService $svc) {
    $b = $svc->create($this->option('type'), null, 'Backup din linia de comandă / cron');
    $svc->prune((int) setting('backups.keep', 10));
    $this->info('Backup creat și verificat: '.$b->filename);
})->purpose('Creează un backup verificat');

Artisan::command('elgarom:restore {file}', function (BackupService $svc) {
    $b = \App\Models\Backup::where('filename', basename($this->argument('file')))->first();
    if (! $b) {
        $path = BackupService::dir().'/'.basename($this->argument('file'));
        if (! is_file($path)) {
            $this->error('Fișierul nu există în '.BackupService::dir());

            return 1;
        }
        $b = \App\Models\Backup::create(['filename' => basename($path), 'type' => 'incarcat', 'status' => 'ok', 'size' => filesize($path), 'sha256' => hash_file('sha256', $path)]);
    }
    if (! $this->confirm('Restaurez '.$b->filename.'? Datele curente vor fi înlocuite (se face întâi un backup de siguranță).')) {
        return 0;
    }
    $safety = $svc->restore($b);
    $this->info('Restaurare finalizată. Backup de siguranță: '.$safety->filename);
})->purpose('Restaurează un backup (bază de date + fișiere)');

Artisan::command('elgarom:update {package}', function (Installer $installer) {
    $rec = $installer->install($this->argument('package'), null);
    AuditLog::record('actualizare.instalata_cli', $rec, ['din' => $rec->version_from, 'in' => $rec->version_to]);
    $this->info('Instalat: '.$rec->version_from.' → '.$rec->version_to);
})->purpose('Instalează un pachet de actualizare semnat (alternativă la panou)');

Artisan::command('elgarom:admin {email} {--name=Administrator}', function () {
    $email = Str::lower($this->argument('email'));
    $password = Str::password(20, true, true, false);
    $user = User::firstOrNew(['email' => $email]);
    $user->forceFill(['name' => $user->name ?? $this->option('name'), 'role' => 'admin', 'is_active' => true, 'password' => Hash::make($password),
        'two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null])->save();
    \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->delete();
    AuditLog::record('utilizator.admin_cli', $user, ['email' => $email]);
    $this->warn('Parolă temporară (afișată o singură dată): '.$password);
    $this->info('La autentificare se configurează din nou 2FA.');
})->purpose('Recuperare acces: creează/resetează un administrator (parolă nouă, 2FA resetat)');

Schedule::command('elgarom:mail')->everyMinute()->withoutOverlapping();
Schedule::command('elgarom:prune')->dailyAt('03:10');
Schedule::command('elgarom:backup')->dailyAt('02:40')->withoutOverlapping();
