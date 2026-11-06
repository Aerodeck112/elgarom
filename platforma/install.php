<?php
/**
 * ELGAROM – instalator pentru cPanel.
 *
 * Pași: verificarea serverului → cheia de instalare → baza de date și administratorul →
 * instalare (migrații, conținut inițial) → verificări finale. După instalare se creează
 * install.lock și instalatorul devine inaccesibil; fișierul install.php poate fi șters.
 */

declare(strict_types=1);

$ROOT = __DIR__;
const MIN_PHP = '8.3.0';
const REQUIRED_EXT = ['pdo_mysql', 'mbstring', 'openssl', 'sodium', 'zip', 'gd', 'fileinfo', 'ctype', 'tokenizer', 'xml', 'dom', 'curl', 'filter', 'json', 'session'];

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
header('Cache-Control: no-store');

if (is_file($ROOT.'/install.lock')) {
    http_response_code(403);
    page('Instalarea a fost finalizată', '<p>Platforma este deja instalată. Instalatorul este blocat de fișierul <code>install.lock</code>.</p><p>Din motive de securitate, ștergeți <code>install.php</code> și <code>public/install.php</code> din cPanel → File Manager, dacă mai există.</p><p><a href="/admin">Mergi la administrare</a></p>');
    exit;
}

session_name('elg_install');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => is_https()]);
session_start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
@set_time_limit(600);

$keyFile = $ROOT.'/storage/app/private/install-key.txt';
if (! is_file($keyFile) && is_writable(dirname($keyFile))) {
    file_put_contents($keyFile, 'ELG-'.strtoupper(bin2hex(random_bytes(10)))."\n");
    @chmod($keyFile, 0600);
}
$installKey = is_file($keyFile) ? trim((string) file_get_contents($keyFile)) : '';

$step = $_POST['step'] ?? 'check';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (! hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        $errors[] = 'Sesiunea formularului a expirat. Reîncărcați pagina.';
        $step = 'check';
    }
}

/* ------------------------------------------------------------------ helpers */
function e(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function is_https(): bool
{
    return (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
}
function bytes(string $v): int
{
    $v = trim($v);
    if ($v === '-1') {
        return PHP_INT_MAX;
    }
    $n = (int) $v;

    return match (strtolower(substr($v, -1))) { 'g' => $n * 1024 ** 3, 'm' => $n * 1024 ** 2, 'k' => $n * 1024, default => $n };
}
function page(string $title, string $body): void
{
    echo '<!DOCTYPE html><html lang="ro"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>'.e($title).' – Instalare ELGAROM</title><style>
    body{margin:0;font:16px/1.5 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#20262b;color:#20262b}
    main{max-width:760px;margin:2rem auto;background:#fff;padding:2rem 2.25rem;border-top:4px solid #14ad95}
    h1{font-size:1.6rem;margin:0 0 1rem;color:#28387b}h2{font-size:1.1rem;margin:1.75rem 0 .5rem}
    table{width:100%;border-collapse:collapse;font-size:.92rem}td,th{padding:.45rem .5rem;border-bottom:1px solid #eef0f1;text-align:left;vertical-align:top}
    .ok{color:#0b7a69;font-weight:600}.bad{color:#b3261e;font-weight:600}.warn{color:#8a5a00;font-weight:600}
    label{display:block;font-weight:600;font-size:.9rem;margin:.9rem 0 .25rem}input{width:100%;box-sizing:border-box;padding:.6rem .7rem;border:1.5px solid #b9c1c7;font:inherit}
    input:focus{outline:3px solid #14ad95;outline-offset:1px}.hint{font-size:.82rem;color:#5c666e;margin:.25rem 0 0}
    button{margin-top:1.5rem;background:#14ad95;color:#20262b;border:0;padding:.8rem 1.4rem;font:inherit;font-weight:700;cursor:pointer}
    .err{background:#fbeeed;border-left:4px solid #b3261e;padding:.8rem 1rem;margin:0 0 1rem}.note{background:#eef0f1;padding:.8rem 1rem;margin:1rem 0;font-size:.9rem}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:0 1rem}@media(max-width:640px){.grid{grid-template-columns:1fr}}code{background:#eef0f1;padding:0 .25rem}
    </style></head><body><main><h1>'.e($title).'</h1>'.$body.'</main></body></html>';
}

function checks(string $root): array
{
    $c = [];
    $c[] = ['PHP '.MIN_PHP.' sau mai nou', version_compare(PHP_VERSION, MIN_PHP, '>='), 'Versiune actuală: '.PHP_VERSION.'. În cPanel: MultiPHP Manager / Select PHP Version.', true];
    foreach (REQUIRED_EXT as $ext) {
        $c[] = ['Extensia '.$ext, extension_loaded($ext), 'Activați-o în cPanel → Select PHP Version → Extensions.', true];
    }
    $c[] = ['GD cu suport WebP', function_exists('imagewebp'), 'Necesar pentru imagini optimizate. Fără el, imaginile se servesc doar în JPEG/PNG.', false];
    foreach (['storage', 'storage/framework', 'storage/logs', 'storage/app/private', 'bootstrap/cache', 'public', 'public/media'] as $dir) {
        $p = $root.'/'.$dir;
        if (! is_dir($p)) {
            @mkdir($p, 0755, true);
        }
        $c[] = ['Director scriibil: '.$dir, is_dir($p) && is_writable($p), 'Setați permisiunile la 755 (File Manager → Permissions).', true];
    }
    $c[] = ['Directorul aplicației scriibil (pentru .env și install.lock)', is_writable($root), 'Permisiuni 755 pe directorul în care ați dezarhivat pachetul.', true];
    $c[] = ['vendor/ prezent', is_file($root.'/vendor/autoload.php'), 'Pachetul pare incomplet. Dezarhivați din nou arhiva completă.', true];
    $c[] = ['Memorie PHP ≥ 256M', bytes((string) ini_get('memory_limit')) >= 256 * 1024 * 1024, 'memory_limit = '.ini_get('memory_limit').'. Recomandat 256M (MultiPHP INI Editor). Minim 128M.', false];
    $c[] = ['Timp de execuție ≥ 60 s', (int) ini_get('max_execution_time') === 0 || (int) ini_get('max_execution_time') >= 60, 'max_execution_time = '.ini_get('max_execution_time').'. Recomandat 120 pentru backup și actualizări.', false];
    $c[] = ['Upload ≥ 32M', bytes((string) ini_get('upload_max_filesize')) >= 32 * 1024 * 1024, 'upload_max_filesize = '.ini_get('upload_max_filesize').', post_max_size = '.ini_get('post_max_size').'. Pachetele de actualizare mari se pot urca și prin File Manager.', false];
    $c[] = ['HTTPS', is_https(), 'Activați certificatul SSL (cPanel → SSL/TLS Status → AutoSSL) înainte de a folosi site-ul.', false];

    return $c;
}

function test_db(array $d): ?string
{
    try {
        $pdo = new PDO('mysql:host='.$d['db_host'].';port='.$d['db_port'].';dbname='.$d['db_name'].';charset=utf8mb4', $d['db_user'], $d['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8]);
        $v = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        $isMaria = stripos($v, 'mariadb') !== false;
        if ((! $isMaria && version_compare($v, '8.0.0', '<')) || ($isMaria && version_compare($v, '10.3.0', '<'))) {
            return 'Versiunea bazei de date ('.$v.') este prea veche. Necesar: MySQL 8.0+ sau MariaDB 10.3+.';
        }
        $tables = (int) $pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn();
        if ($tables > 0) {
            return 'Baza de date nu este goală ('.$tables.' tabele). Folosiți o bază de date nouă, creată în cPanel → MySQL Databases.';
        }
    } catch (Throwable $e) {
        return 'Conexiunea la baza de date a eșuat: '.$e->getMessage();
    }

    return null;
}

function env_value(string $v): string
{
    return '"'.str_replace(['\\', '"', "\n", '$'], ['\\\\', '\\"', '', '\\$'], $v).'"';
}

function password_problem(string $p, string $email): ?string
{
    if (mb_strlen($p) < 12) {
        return 'Parola trebuie să aibă cel puțin 12 caractere.';
    }
    if (! preg_match('/[a-z]/', $p) || ! preg_match('/[A-Z]/', $p) || ! preg_match('/\d/', $p)) {
        return 'Parola trebuie să conțină litere mici, litere mari și cifre.';
    }
    if (str_contains(strtolower($p), strtolower(strtok($email, '@')))) {
        return 'Parola nu poate conține numele contului de email.';
    }

    return null;
}

/* -------------------------------------------------------------------- steps */
$checks = checks($ROOT);
$blocking = array_filter($checks, fn ($c) => $c[3] && ! $c[1]);
$scheme = is_https() ? 'https' : 'http';
$defaultUrl = $scheme.'://'.preg_replace('/[^a-z0-9.\-:]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));

if ($step === 'install' && ! $errors) {
    $d = [
        'app_url' => rtrim(trim((string) ($_POST['app_url'] ?? '')), '/'),
        'db_host' => trim((string) ($_POST['db_host'] ?? 'localhost')),
        'db_port' => (string) (int) ($_POST['db_port'] ?? 3306),
        'db_name' => trim((string) ($_POST['db_name'] ?? '')),
        'db_user' => trim((string) ($_POST['db_user'] ?? '')),
        'db_pass' => (string) ($_POST['db_pass'] ?? ''),
        'admin_name' => trim((string) ($_POST['admin_name'] ?? '')),
        'admin_email' => strtolower(trim((string) ($_POST['admin_email'] ?? ''))),
        'admin_pass' => (string) ($_POST['admin_pass'] ?? ''),
        'admin_pass2' => (string) ($_POST['admin_pass2'] ?? ''),
        'notify' => strtolower(trim((string) ($_POST['notify'] ?? ''))),
    ];
    if ($blocking) {
        $errors[] = 'Serverul nu îndeplinește cerințele obligatorii.';
    }
    if ($installKey === '' || ! hash_equals($installKey, trim((string) ($_POST['install_key'] ?? '')))) {
        $errors[] = 'Cheia de instalare este incorectă. O găsiți în fișierul storage/app/private/install-key.txt (cPanel → File Manager).';
    }
    if (! filter_var($d['app_url'], FILTER_VALIDATE_URL) || ! preg_match('#^https?://[^/]+$#', $d['app_url'])) {
        $errors[] = 'Adresa site-ului trebuie să fie de forma https://www.exemplu.ro (fără / la final).';
    }
    if ($d['db_name'] === '' || $d['db_user'] === '' || ! preg_match('/^[A-Za-z0-9_.\-]+$/', $d['db_host'])) {
        $errors[] = 'Completați datele bazei de date.';
    }
    if ($d['admin_name'] === '' || ! filter_var($d['admin_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Completați numele și emailul administratorului.';
    }
    if ($d['admin_pass'] !== $d['admin_pass2']) {
        $errors[] = 'Parolele administratorului nu coincid.';
    } elseif ($p = password_problem($d['admin_pass'], $d['admin_email'])) {
        $errors[] = $p;
    }
    if ($d['notify'] !== '' && ! filter_var($d['notify'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresa pentru notificări nu este validă.';
    }
    if (! $errors && ($dbErr = test_db($d))) {
        $errors[] = $dbErr;
    }

    if (! $errors) {
        $log = [];
        try {
            $key = 'base64:'.base64_encode(random_bytes(32));
            $https = str_starts_with($d['app_url'], 'https://');
            $env = implode("\n", [
                'APP_NAME=ELGAROM', 'APP_ENV=production', 'APP_KEY='.$key, 'APP_DEBUG=false', 'APP_URL='.$d['app_url'],
                'APP_LOCALE=ro', 'APP_FALLBACK_LOCALE=ro', 'APP_TIMEZONE=Europe/Bucharest',
                'LOG_CHANNEL=daily', 'LOG_LEVEL=warning', 'LOG_DAILY_DAYS=30',
                'DB_CONNECTION=mysql', 'DB_HOST='.env_value($d['db_host']), 'DB_PORT='.$d['db_port'], 'DB_DATABASE='.env_value($d['db_name']),
                'DB_USERNAME='.env_value($d['db_user']), 'DB_PASSWORD='.env_value($d['db_pass']),
                'SESSION_DRIVER=database', 'SESSION_LIFETIME=120', 'SESSION_ENCRYPT=true', 'SESSION_SECURE_COOKIE='.($https ? 'true' : 'false'), 'SESSION_SAME_SITE=lax',
                'CACHE_STORE=file', 'QUEUE_CONNECTION=sync', 'MAIL_MAILER=smtp', 'FILESYSTEM_DISK=local', 'BCRYPT_ROUNDS=12', '',
            ]);
            if (file_put_contents($ROOT.'/.env', $env) === false) {
                throw new RuntimeException('Fișierul .env nu poate fi scris.');
            }
            @chmod($ROOT.'/.env', 0600);
            $log[] = 'Fișierul .env a fost creat (cheie de criptare nouă, depanare dezactivată).';

            require $ROOT.'/vendor/autoload.php';
            $app = require $ROOT.'/bootstrap/app.php';
            $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();

            Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            $log[] = 'Tabelele bazei de date au fost create.';
            Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
            $log[] = 'Conținutul inițial (pagini, servicii, imagini, redirect-uri) a fost importat.';

            $user = new App\Models\User(['name' => $d['admin_name'], 'email' => $d['admin_email'], 'role' => 'admin', 'is_active' => true]);
            $user->password = Illuminate\Support\Facades\Hash::make($d['admin_pass']);
            $user->password_changed_at = now();
            $user->save();
            $log[] = 'Administratorul '.$d['admin_email'].' a fost creat. La prima autentificare configurați 2FA.';

            if ($d['notify'] !== '') {
                App\Models\Setting::put('notifications.recipients', [$d['notify']]);
            }
            file_put_contents($ROOT.'/storage/app/private/health.token', bin2hex(random_bytes(24)));
            App\Models\AuditLog::record('instalare.finalizata', $user, ['versiune' => config('elgarom.version')], $user->id);

            Illuminate\Support\Facades\Artisan::call('optimize');
            $log[] = 'Cache-urile de configurare, rute și vederi au fost generate.';

            file_put_contents($ROOT.'/install.lock', 'Instalat la '.date('c').' – versiunea '.config('elgarom.version')."\n");
            @unlink($keyFile);
            $deleted = @unlink($ROOT.'/public/install.php');
            $deleted = @unlink($ROOT.'/install.php') && $deleted;
            $log[] = $deleted ? 'Instalatorul a fost șters.' : 'Instalatorul este blocat (install.lock). Ștergeți manual install.php și public/install.php.';
        } catch (Throwable $e) {
            $errors[] = 'Instalarea a eșuat: '.$e->getMessage();
            $errors[] = 'Corectați problema, ștergeți tabelele create (phpMyAdmin) și fișierul .env, apoi reluați instalarea.';
        }

        if (! $errors) {
            // Post-install HTTP checks
            $results = [];
            $ctx = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true, 'follow_location' => 0]]);
            foreach (['/health' => 200, '/.env' => 'not200', '/storage/logs/laravel.log' => 'not200', '/vendor/autoload.php' => 'not200'] as $path => $expect) {
                $hdrs = @get_headers($d['app_url'].$path, false, $ctx);
                $code = $hdrs ? (int) (explode(' ', $hdrs[0])[1] ?? 0) : 0;
                $ok = $expect === 200 ? $code === 200 : ($code !== 200);
                $results[] = [$path, $code ?: 'fără răspuns', $ok, $expect === 200 ? 'trebuie să răspundă' : 'nu trebuie să fie accesibil'];
            }
            $html = '<p class="ok">Instalarea a reușit.</p><ul>';
            foreach ($log as $l) {
                $html .= '<li>'.e($l).'</li>';
            }
            $html .= '</ul><h2>Verificări după instalare</h2><table><tr><th>Adresă</th><th>Cod HTTP</th><th>Rezultat</th></tr>';
            foreach ($results as [$p, $c, $ok, $exp]) {
                $html .= '<tr><td><code>'.e($p).'</code> ('.e($exp).')</td><td>'.e((string) $c).'</td><td class="'.($ok ? 'ok' : 'bad').'">'.($ok ? 'OK' : 'VERIFICAȚI').'</td></tr>';
            }
            $php = e(PHP_BINARY && ! str_contains(PHP_BINARY, 'fpm') ? PHP_BINARY : '/usr/local/bin/php');
            $html .= '</table><p class="hint">Dacă /.env răspunde cu 200, mutați documentul rădăcină al domeniului pe directorul <code>public</code> (cPanel → Domains) – vezi docs/INSTALARE.md.</p>
            <h2>Pas obligatoriu: cron</h2><p>În cPanel → Cron Jobs, adăugați o sarcină care rulează în fiecare minut:</p>
            <p><code>* * * * * '.$php.' '.e($ROOT).'/artisan schedule:run &gt;/dev/null 2&gt;&amp;1</code></p>
            <p class="hint">Cron-ul trimite emailurile din coadă și le reîncearcă, face backup zilnic și aplică retenția datelor. Fără cron, emailurile se reîncearcă doar la vizitele în administrare.</p>
            <h2>Următorii pași</h2><ol><li><a href="'.e($d['app_url']).'/admin">Autentificați-vă în administrare</a> și configurați 2FA.</li><li>Configurați SMTP (Administrare → SMTP și notificări) și trimiteți un email de test.</li><li>Completați lista „De completat” din panoul principal.</li><li>Creați primul backup (Administrare → Backup).</li></ol>';
            session_destroy();
            page('Instalare finalizată', $html);
            exit;
        }
    }
    $step = 'form';
}

/* -------------------------------------------------------------------- render */
$errHtml = $errors ? '<div class="err"><strong>Corectați:</strong><ul><li>'.implode('</li><li>', array_map('e', $errors)).'</li></ul></div>' : '';
$rows = '';
foreach ($checks as [$label, $ok, $hint, $req]) {
    $rows .= '<tr><td>'.e($label).($req ? '' : ' <span class="hint">(recomandat)</span>').'</td><td class="'.($ok ? 'ok' : ($req ? 'bad' : 'warn')).'">'.($ok ? 'OK' : ($req ? 'Lipsește' : 'Atenție')).'</td><td class="hint">'.($ok ? '' : e($hint)).'</td></tr>';
}
$v = fn ($k, $def = '') => e((string) ($_POST[$k] ?? $def));
$body = $errHtml.'<p>Instalatorul verifică serverul, creează fișierul de configurare, baza de date, conținutul inițial și contul de administrator.</p>
<h2>1. Verificarea serverului</h2><table>'.$rows.'</table>';
if ($blocking) {
    $body .= '<p class="bad">Rezolvați cerințele obligatorii marcate „Lipsește”, apoi reîncărcați pagina.</p>';
} else {
    $body .= '<form method="post" autocomplete="off"><input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'"><input type="hidden" name="step" value="install">
    <h2>2. Cheia de instalare</h2>
    <p class="hint">Protejează instalarea împotriva altor persoane. Deschideți în cPanel → File Manager fișierul <code>storage/app/private/install-key.txt</code> și copiați conținutul.</p>
    <label for="install_key">Cheie de instalare</label><input id="install_key" name="install_key" required>
    <h2>3. Site</h2><label for="app_url">Adresa site-ului</label><input id="app_url" name="app_url" value="'.$v('app_url', $defaultUrl).'" required><p class="hint">Cu https:// și fără / la final. Exemplu: https://www.elgarom.ro</p>
    <h2>4. Baza de date</h2><p class="hint">Creați în cPanel → MySQL Databases o bază de date nouă și un utilizator cu toate privilegiile pe ea.</p>
    <div class="grid"><div><label for="db_host">Server</label><input id="db_host" name="db_host" value="'.$v('db_host', 'localhost').'" required></div><div><label for="db_port">Port</label><input id="db_port" name="db_port" value="'.$v('db_port', '3306').'" required></div>
    <div><label for="db_name">Nume bază de date</label><input id="db_name" name="db_name" value="'.$v('db_name').'" required></div><div><label for="db_user">Utilizator</label><input id="db_user" name="db_user" value="'.$v('db_user').'" required></div></div>
    <label for="db_pass">Parolă bază de date</label><input id="db_pass" name="db_pass" type="password" required>
    <h2>5. Administrator</h2><div class="grid"><div><label for="admin_name">Nume</label><input id="admin_name" name="admin_name" value="'.$v('admin_name').'" required></div><div><label for="admin_email">Email</label><input id="admin_email" name="admin_email" type="email" value="'.$v('admin_email').'" required></div>
    <div><label for="admin_pass">Parolă</label><input id="admin_pass" name="admin_pass" type="password" minlength="12" required></div><div><label for="admin_pass2">Confirmare parolă</label><input id="admin_pass2" name="admin_pass2" type="password" minlength="12" required></div></div>
    <p class="hint">Minimum 12 caractere, cu litere mari, mici și cifre. La prima autentificare se configurează obligatoriu 2FA.</p>
    <label for="notify">Email pentru notificări cereri noi <span class="hint">(opțional)</span></label><input id="notify" name="notify" type="email" value="'.$v('notify', 'vanzari@elgarom.ro').'">
    <button type="submit">Instalează</button></form>';
}
page('Instalare ELGAROM', $body);
