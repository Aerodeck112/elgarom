<?php

use App\Calc\ModelRegistry;
use App\Calc\ModelValidator;
use App\Models\AuditLog;
use App\Models\Backup;
use App\Models\CalcModelVersion;
use App\Models\User;
use App\Services\BackupService;
use App\Services\Mail\Outbox;
use App\Services\Updates\Installer;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Artisan::command('simex:mail', function () {
    [$sent, $failed] = app(Outbox::class)->process(50);
    $this->info("Emailuri trimise: $sent, eșuate: $failed");
})->purpose('Trimite emailurile din coadă (cu reîncercare)');

Artisan::command('simex:prune', function () {
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
    AuditLog::where('created_at', '<', now()->subDays((int) setting('security.audit_days', 365)))->delete();
    $this->info("Directoare temporare șterse: $tmp");
})->purpose('Aplică retenția (fișiere temporare, jurnal)');

Artisan::command('simex:backup {--type=complet}', function (BackupService $svc) {
    $b = $svc->create($this->option('type'), null, 'Backup din linia de comandă / cron');
    $svc->prune((int) setting('backups.keep', 10));
    $this->info('Backup creat și verificat: '.$b->filename);
})->purpose('Creează un backup verificat');

Artisan::command('simex:restore {file}', function (BackupService $svc) {
    $b = Backup::where('filename', basename($this->argument('file')))->first();
    if (! $b) {
        $path = BackupService::dir().'/'.basename($this->argument('file'));
        if (! is_file($path)) {
            $this->error('Fișierul nu există în '.BackupService::dir());

            return 1;
        }
        $b = Backup::create(['filename' => basename($path), 'type' => 'incarcat', 'status' => 'ok', 'size' => filesize($path), 'sha256' => hash_file('sha256', $path)]);
    }
    if (! $this->confirm('Restaurez '.$b->filename.'? Datele curente vor fi înlocuite (se face întâi un backup de siguranță).')) {
        return 0;
    }
    $safety = $svc->restore($b);
    $this->info('Restaurare finalizată. Backup de siguranță: '.$safety->filename);
})->purpose('Restaurează un backup (bază de date + fișiere)');

Artisan::command('simex:update {package}', function (Installer $installer) {
    $rec = $installer->install($this->argument('package'), null);
    AuditLog::record('actualizare.instalata_cli', $rec, ['din' => $rec->version_from, 'in' => $rec->version_to]);
    $this->info('Instalat: '.$rec->version_from.' → '.$rec->version_to);
})->purpose('Instalează un pachet de actualizare semnat (alternativă la panou)');

Artisan::command('simex:admin {email} {--name=Administrator}', function () {
    $email = Str::lower($this->argument('email'));
    $password = Str::password(20, true, true, false);
    $user = User::firstOrNew(['email' => $email]);
    $user->forceFill(['name' => $user->name ?? $this->option('name'), 'role' => 'super_admin', 'is_active' => true, 'password' => Hash::make($password),
        'two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null])->save();
    DB::table('sessions')->where('user_id', $user->id)->delete();
    AuditLog::record('utilizator.admin_cli', $user, ['email' => $email]);
    $this->warn('Parolă temporară (afișată o singură dată): '.$password);
    $this->info('La autentificare se configurează din nou 2FA.');
})->purpose('Recuperare acces: creează/resetează un administrator (parolă nouă, 2FA resetat)');

Schedule::command('simex:mail')->everyMinute()->withoutOverlapping();
Schedule::command('simex:prune')->dailyAt('03:10');
Schedule::command('simex:backup')->dailyAt('02:40')->withoutOverlapping();

Artisan::command('simex:modele {dir}', function (ModelRegistry $registry, ModelValidator $validator) {
    $dir = rtrim($this->argument('dir'), '/');
    foreach ($registry->all() as $def) {
        $file = $dir.'/'.$def->file_hint;
        if (! is_file($file)) {
            $this->line("– {$def->key()}: lipsește {$def->file_hint}");

            continue;
        }
        $report = $validator->validate($file, $def);
        if (! $report['ok']) {
            $this->error("✗ {$def->key()}: ".implode(' ', $report['errors']));

            continue;
        }
        $path = 'calc-models/'.$def->key().'/'.$report['sha256'].'.xlsx';
        Storage::disk('local')->put($path, file_get_contents($file));
        CalcModelVersion::where('model_key', $def->key())->where('status', 'activ')->where('sha256', '!=', $report['sha256'])->update(['status' => 'retras']);
        $v = CalcModelVersion::updateOrCreate(['model_key' => $def->key(), 'sha256' => $report['sha256']], [
            'original_name' => basename($file), 'path' => $path, 'size' => filesize($file), 'status' => 'activ', 'formulas' => $report['formulas'], 'mismatches' => 0, 'report' => $report, 'activated_at' => now(),
        ]);
        AuditLog::record('model.activat_cli', $v, ['model' => $def->key(), 'formule' => $report['formulas']]);
        $this->info("✓ {$def->key()}: {$report['formulas']} formule, 0 diferențe – activ");
    }
})->purpose('Validează și activează modelele Excel dintr-un director (alternativă la încărcarea din panou)');
