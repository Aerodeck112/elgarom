<?php
/**
 * ELGAROM – utilitar de release (rulează pe calculatorul de dezvoltare, NU pe hosting).
 *
 *   php tools/release.php keygen <director>
 *       Generează o pereche de chei Ed25519. Cheia publică se pune în platforma/config/elgarom.php
 *       (updates.public_keys); cheia privată se păstrează offline (manager de parole / stick criptat).
 *
 *   php tools/release.php package --version=1.0.0
 *       Construiește pachetul complet pentru cPanel (cu install.php și vendor/) în dist/
 *       și lista de fișiere dist/files-1.0.0.json, necesară pentru următoarea actualizare.
 *
 *   php tools/release.php update --version=1.1.0 --min-from=1.0.0 --previous=dist/files-1.0.0.json \
 *       --key=/cale/catre/cheie.secret --changelog=CHANGELOG-1.1.0.md [--key-id=elgarom-release-2026]
 *       Construiește un pachet de actualizare semnat: manifest.json + manifest.sig + files/.
 *
 *   php tools/release.php verify <pachet.zip>
 *       Verifică semnătura și hash-urile cu cheile publice din config/elgarom.php.
 *
 * Opțiuni comune: --skip-build (nu rulează npm/composer; folosește build/ existent).
 */
if (PHP_SAPI !== 'cli') {
    exit(1);
}
$repo = dirname(__DIR__);
$src = $repo.'/platforma';
$buildDir = $repo.'/build/platforma';
$dist = $repo.'/dist';
@mkdir($dist, 0755, true);

$cmd = $argv[1] ?? 'help';
$opts = [];
$pos = [];
foreach (array_slice($argv, 2) as $a) {
    if (preg_match('/^--([a-z\-]+)(?:=(.*))?$/', $a, $m)) {
        $opts[$m[1]] = $m[2] ?? true;
    } else {
        $pos[] = $a;
    }
}

function say(string $m): void { fwrite(STDOUT, $m.PHP_EOL); }
function die_(string $m): never { fwrite(STDERR, 'EROARE: '.$m.PHP_EOL); exit(1); }
function run(string $cmd, string $cwd): void
{
    say('$ '.$cmd);
    $p = proc_open($cmd, [1 => STDOUT, 2 => STDERR], $pipes, $cwd);
    if (! is_resource($p) || proc_close($p) !== 0) {
        die_('Comanda a eșuat: '.$cmd);
    }
}
function rrmdir(string $d): void
{
    if (! is_dir($d)) { return; }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) { $f->isDir() && ! $f->isLink() ? rmdir($f->getPathname()) : unlink($f->getPathname()); }
    rmdir($d);
}

/** Paths (relative) excluded from the build copy. */
const EXCLUDE = [
    '#^node_modules(/|$)#', '#^vendor(/|$)#', '#^tests(/|$)#', '#^\.env($|\.)#', '#^\.phpunit#', '#^phpunit\.xml$#',
    '#^storage/logs/.+#', '#^storage/framework/(cache|sessions|views|testing)/.+#', '#^storage/app/private/.+#', '#^storage/app/public/.+#',
    '#^public/media/(?!\.htaccess$).+#', '#^public/hot$#', '#^bootstrap/cache/.+\.php$#', '#^database/.+\.sqlite$#',
    '#^\.git#', '#^\.npmrc$#', '#^\.styleci\.yml$#', '#^\.idea#', '#^\.vscode#', '#^\.editorconfig$#', '#^\.gitattributes$#', '#^README\.md$#', '#^CHANGELOG\.md$#',
    '#^package(-lock)?\.json$#', '#^vite\.config\.js$#', '#^resources/(js|css)/#',
];
/** Paths an update must never contain (user data, secrets, installer). */
const NOT_IN_UPDATE = ['#^\.env#', '#^storage/#', '#^public/media/#', '#^bootstrap/cache/#', '#^install\.lock$#', '#^install\.php$#', '#^public/install\.php$#'];

function excluded(string $rel, array $patterns): bool
{
    foreach ($patterns as $p) { if (preg_match($p, $rel)) { return true; } }
    return false;
}

function files_of(string $root): array
{
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isLink()) { die_('Link simbolic în build: '.$f->getPathname()); }
        if ($f->isFile()) { $out[] = ltrim(substr($f->getPathname(), strlen($root)), '/'); }
    }
    sort($out);
    return $out;
}

function build(string $src, string $buildDir, string $version, bool $skip): void
{
    if (! preg_match('/^\d+\.\d+\.\d+$/', $version)) { die_('Versiune invalidă: '.$version); }
    $setVersion = function () use ($buildDir, $version) {
        $cfg = $buildDir.'/config/elgarom.php';
        file_put_contents($cfg, preg_replace("/'version' => '[^']+'/", "'version' => '".$version."'", file_get_contents($cfg), 1));
    };
    if ($skip && is_dir($buildDir)) { say('Folosesc build-ul existent.'); $setVersion(); return; }
    run('npm run build', $src);
    rrmdir($buildDir);
    @mkdir($buildDir, 0755, true);
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($it as $f) {
        $rel = ltrim(substr($f->getPathname(), strlen($src)), '/');
        if (excluded($rel, EXCLUDE)) { continue; }
        $dest = $buildDir.'/'.$rel;
        if ($f->isDir()) { @mkdir($dest, 0755, true); } elseif (! $f->isLink()) { copy($f->getPathname(), $dest); }
    }
    foreach (['storage/logs', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/app/private', 'bootstrap/cache', 'public/media'] as $d) {
        @mkdir($buildDir.'/'.$d, 0755, true);
    }
    $setVersion();
    run('composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress', $buildDir);
    // strip VCS metadata and add deny rules
    // Packages installed from VCS (when dist zips are unavailable) carry tests, docs and symlinks: drop them.
    foreach (['.git', '.github', 'tests', 'test', 'Tests', 'docs', 'doc', 'examples', 'benchmarks', '.phive'] as $junk) {
        foreach (glob($buildDir.'/vendor/*/*/'.$junk, GLOB_ONLYDIR) ?: [] as $g) { rrmdir($g); }
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($buildDir.'/vendor', FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) { if ($f->isLink()) { unlink($f->getPathname()); } }
    run('composer dump-autoload --no-dev --optimize --no-interaction', $buildDir);
    file_put_contents($buildDir.'/vendor/.htaccess', "Require all denied\n");
    @unlink($buildDir.'/composer.json.bak');
}

function zip_dir(string $root, string $zipPath, array $files): void
{
    @unlink($zipPath);
    $z = new ZipArchive();
    if ($z->open($zipPath, ZipArchive::CREATE) !== true) { die_('Nu pot crea '.$zipPath); }
    foreach ($files as $rel) { $z->addFile($root.'/'.$rel, $rel); $z->setExternalAttributesName($rel, ZipArchive::OPSYS_UNIX, 0100644 << 16); }
    // keep empty runtime directories
    foreach (['storage/logs/', 'storage/framework/cache/data/', 'storage/framework/sessions/', 'storage/framework/views/', 'storage/app/private/', 'bootstrap/cache/', 'public/media/'] as $d) { $z->addEmptyDir(rtrim($d, '/')); }
    $z->close();
}

function public_keys(string $src): array
{
    $cfg = file_get_contents($src.'/config/elgarom.php');
    preg_match_all("/'([a-z0-9\-]+)' => '([A-Za-z0-9+\/=]{40,})'/", $cfg, $m, PREG_SET_ORDER);
    return array_column($m, 2, 1);
}

switch ($cmd) {
    case 'keygen':
        $dir = $pos[0] ?? die_('Indicați directorul pentru chei.');
        @mkdir($dir, 0700, true);
        $kp = sodium_crypto_sign_keypair();
        $id = 'elgarom-release-'.date('Y');
        file_put_contents($dir.'/'.$id.'.secret', base64_encode(sodium_crypto_sign_secretkey($kp))."\n");
        chmod($dir.'/'.$id.'.secret', 0600);
        file_put_contents($dir.'/'.$id.'.public', base64_encode(sodium_crypto_sign_publickey($kp))."\n");
        say("Cheie privată: $dir/$id.secret  (NU o urcați pe server, NU o puneți în git)");
        say('Cheie publică (de adăugat în config/elgarom.php → updates.public_keys):');
        say("    '$id' => '".base64_encode(sodium_crypto_sign_publickey($kp))."',");
        break;

    case 'package':
        $v = $opts['version'] ?? die_('--version lipsește');
        build($src, $buildDir, $v, isset($opts['skip-build']));
        $files = files_of($buildDir);
        $zip = $dist.'/elgarom-'.$v.'-cpanel.zip';
        zip_dir($buildDir, $zip, $files);
        $list = [];
        foreach ($files as $rel) { if (! excluded($rel, NOT_IN_UPDATE)) { $list[$rel] = hash_file('sha256', $buildDir.'/'.$rel); } }
        file_put_contents($dist.'/files-'.$v.'.json', json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        say('Pachet: '.$zip.' ('.number_format(filesize($zip) / 1048576, 1).' MB, '.count($files).' fișiere)');
        say('SHA-256: '.hash_file('sha256', $zip));
        break;

    case 'update':
        $v = $opts['version'] ?? die_('--version lipsește');
        $min = $opts['min-from'] ?? die_('--min-from lipsește');
        $keyFile = $opts['key'] ?? die_('--key lipsește (cheia privată)');
        $secret = base64_decode(trim((string) file_get_contents($keyFile)), true);
        if (! $secret || strlen($secret) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) { die_('Cheie privată invalidă.'); }
        $pub = base64_encode(sodium_crypto_sign_publickey_from_secretkey($secret));
        $keyId = $opts['key-id'] ?? array_search($pub, public_keys($src), true);
        if (! $keyId) { die_('Cheia publică corespunzătoare nu se află în config/elgarom.php; pachetul ar fi respins.'); }
        build($src, $buildDir, $v, isset($opts['skip-build']));
        $files = [];
        foreach (files_of($buildDir) as $rel) { if (! excluded($rel, NOT_IN_UPDATE)) { $files[$rel] = hash_file('sha256', $buildDir.'/'.$rel); } }
        $prev = isset($opts['previous']) ? json_decode((string) file_get_contents($opts['previous']), true) : [];
        $delete = array_values(array_diff(array_keys($prev ?: []), array_keys($files)));
        $manifest = [
            'format' => 1, 'app' => 'elgarom', 'key_id' => $keyId, 'version' => $v, 'min_from' => $min,
            'php_min' => '8.3.0', 'extensions' => ['pdo_mysql', 'mbstring', 'openssl', 'sodium', 'zip', 'gd', 'fileinfo'],
            'released_at' => date('c'),
            'changelog' => isset($opts['changelog']) ? trim((string) file_get_contents($opts['changelog'])) : 'Versiunea '.$v,
            'files' => $files, 'delete' => $delete,
        ];
        $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $sig = base64_encode(sodium_crypto_sign_detached($json, $secret));
        sodium_memzero($secret);
        $zip = $dist.'/elgarom-update-'.$v.'.zip';
        @unlink($zip);
        $z = new ZipArchive();
        $z->open($zip, ZipArchive::CREATE);
        $z->addFromString('manifest.json', $json);
        $z->addFromString('manifest.sig', $sig."\n");
        foreach (array_keys($files) as $rel) { $z->addFile($buildDir.'/'.$rel, 'files/'.$rel); }
        $z->close();
        file_put_contents($dist.'/files-'.$v.'.json', json_encode($files, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        say('Actualizare semnată: '.$zip.' ('.number_format(filesize($zip) / 1048576, 1).' MB, '.count($files).' fișiere, '.count($delete).' ștergeri, cheie '.$keyId.')');
        say('SHA-256: '.hash_file('sha256', $zip));
        break;

    case 'verify':
        $zipPath = $pos[0] ?? die_('Indicați pachetul.');
        $z = new ZipArchive();
        if ($z->open($zipPath, ZipArchive::CHECKCONS) !== true) { die_('Arhivă invalidă.'); }
        $json = $z->getFromName('manifest.json');
        $sig = base64_decode(trim((string) $z->getFromName('manifest.sig')), true);
        $ok = null;
        foreach (public_keys($src) as $id => $pk) { if ($sig && sodium_crypto_sign_verify_detached($sig, (string) $json, base64_decode($pk))) { $ok = $id; } }
        if (! $ok) { die_('Semnătură invalidă.'); }
        $m = json_decode($json, true);
        foreach ($m['files'] as $rel => $h) { if (hash('sha256', (string) $z->getFromName('files/'.$rel)) !== $h) { die_('Hash greșit: '.$rel); } }
        say("Pachet valid: versiunea {$m['version']} (min. {$m['min_from']}), semnat cu $ok, ".count($m['files']).' fișiere.');
        break;

    default:
        say(file_get_contents(__FILE__, false, null, 0, 1600));
}
