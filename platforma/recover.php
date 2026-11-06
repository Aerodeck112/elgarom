<?php
/**
 * ELGAROM – recuperare de urgență, independentă de framework.
 * Se rulează DOAR din linia de comandă (cPanel → Terminal, sau o sarcină cron temporară):
 *
 *   php recover.php status
 *   php recover.php list
 *   php recover.php maintenance-off
 *   php recover.php restore-code  <fisier-backup.zip>   # restaurează fișierele de cod salvate înainte de o actualizare
 *   php recover.php restore-db    <fisier-backup.zip>   # restaurează baza de date din backup
 *
 * Folosiți-l când panoul de administrare nu mai pornește după o actualizare eșuată.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$root = __DIR__;
$backups = $root.'/storage/app/private/backups';
$cmd = $argv[1] ?? 'help';
$arg = $argv[2] ?? null;

function out(string $m): void { fwrite(STDOUT, $m.PHP_EOL); }
function fail(string $m): never { fwrite(STDERR, 'EROARE: '.$m.PHP_EOL); exit(1); }
function env_read(string $root): array
{
    $env = [];
    foreach (file($root.'/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if (str_starts_with(trim($line), '#') || ! str_contains($line, '=')) { continue; }
        [$k, $v] = explode('=', $line, 2);
        $v = trim($v);
        if (strlen($v) > 1 && $v[0] === '"' && str_ends_with($v, '"')) { $v = stripcslashes(substr($v, 1, -1)); }
        $env[trim($k)] = $v;
    }
    return $env;
}
function open_backup(string $backups, ?string $name): ZipArchive
{
    if (! $name) { fail('Indicați fișierul de backup (vezi: php recover.php list).'); }
    $path = is_file($name) ? $name : $backups.'/'.basename($name);
    $zip = new ZipArchive();
    if (! is_file($path) || $zip->open($path, ZipArchive::CHECKCONS) !== true) { fail('Backup-ul nu există sau este corupt: '.$path); }
    $m = json_decode((string) $zip->getFromName('manifest.json'), true);
    if (($m['app'] ?? '') !== 'elgarom') { fail('Arhiva nu este un backup ELGAROM.'); }
    foreach ($m['files'] as $n => $h) {
        if (hash('sha256', (string) $zip->getFromName($n)) !== $h) { fail('Hash incorect în backup: '.$n); }
    }
    out('Backup verificat: '.basename($path).' (tip '.$m['type'].', versiune '.$m['app_version'].', '.$m['created_at'].')');
    return $zip;
}
function safe(string $rel): bool
{
    return $rel !== '' && ! str_contains($rel, '..') && ! str_starts_with($rel, '/') && ! str_contains($rel, "\0") && ! str_contains($rel, '\\');
}

switch ($cmd) {
    case 'status':
        out('Director: '.$root);
        out('Mentenanță: '.(is_file($root.'/storage/framework/down') ? 'ACTIVĂ' : 'inactivă'));
        out('Instalat: '.(is_file($root.'/install.lock') ? trim(file_get_contents($root.'/install.lock')) : 'nu'));
        if (preg_match("/'version' => '([^']+)'/", (string) @file_get_contents($root.'/config/elgarom.php'), $mm)) { out('Versiune cod: '.$mm[1]); }
        break;
    case 'list':
        foreach (glob($backups.'/*.zip') ?: [] as $f) { out(date('Y-m-d H:i', filemtime($f)).'  '.str_pad(number_format(filesize($f) / 1048576, 1).' MB', 10).'  '.basename($f)); }
        break;
    case 'maintenance-off':
        @unlink($root.'/storage/framework/down');
        @unlink($root.'/bootstrap/cache/config.php');
        @unlink($root.'/bootstrap/cache/routes-v7.php');
        @unlink($root.'/bootstrap/cache/events.php');
        out('Modul mentenanță a fost dezactivat și cache-urile șterse.');
        break;
    case 'restore-code':
        $zip = open_backup($backups, $arg);
        $m = json_decode($zip->getFromName('manifest.json'), true);
        $n = 0;
        foreach (array_keys($m['files']) as $name) {
            if (! str_starts_with($name, 'code/')) { continue; }
            $rel = substr($name, 5);
            if (! safe($rel)) { continue; }
            @mkdir(dirname($root.'/'.$rel), 0755, true);
            file_put_contents($root.'/'.$rel, $zip->getFromName($name));
            $n++;
        }
        foreach (['config.php', 'routes-v7.php', 'events.php', 'services.php', 'packages.php'] as $c) { @unlink($root.'/bootstrap/cache/'.$c); }
        if (function_exists('opcache_reset')) { @opcache_reset(); }
        out("Fișiere de cod restaurate: $n. Fișierele adăugate de actualizare (dacă există) sunt listate în jurnalul actualizării; ele nu afectează versiunea restaurată decât dacă sunt referite.");
        out('Rulați apoi: php recover.php restore-db '.basename((string) $arg).'  și  php recover.php maintenance-off');
        break;
    case 'restore-db':
        $zip = open_backup($backups, $arg);
        $env = env_read($root);
        $pdo = new PDO('mysql:host='.$env['DB_HOST'].';port='.($env['DB_PORT'] ?? 3306).';dbname='.$env['DB_DATABASE'].';charset=utf8mb4', $env['DB_USERNAME'], $env['DB_PASSWORD'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $m = json_decode($zip->getFromName('manifest.json'), true);
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_NUM) as [$t]) {
            if (! in_array($t, $m['tables'], true) && ! in_array($t, ['sessions', 'cache', 'cache_locks'], true)) { $pdo->exec('DROP TABLE `'.$t.'`'); }
        }
        $count = 0;
        foreach (preg_split('/;\r?\n/', $zip->getFromName('database.sql')) as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '' || str_starts_with($stmt, '--')) { continue; }
            $pdo->exec($stmt);
            $count++;
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        out("Baza de date restaurată ($count instrucțiuni).");
        break;
    default:
        out("Utilizare:\n  php recover.php status\n  php recover.php list\n  php recover.php maintenance-off\n  php recover.php restore-code <backup.zip>\n  php recover.php restore-db <backup.zip>");
}
