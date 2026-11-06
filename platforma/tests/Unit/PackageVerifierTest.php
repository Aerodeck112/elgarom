<?php

namespace Tests\Unit;

use App\Services\Updates\PackageVerifier;
use Tests\TestCase;
use ZipArchive;

class PackageVerifierTest extends TestCase
{
    private string $secret;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $kp = sodium_crypto_sign_keypair();
        $this->secret = sodium_crypto_sign_secretkey($kp);
        config(['elgarom.updates.public_keys' => ['test-key' => base64_encode(sodium_crypto_sign_publickey($kp))]]);
        $this->dir = sys_get_temp_dir().'/elg-pkg-'.uniqid();
        mkdir($this->dir);
    }

    /** Builds a package; $mutate may alter manifest or entries before writing. */
    private function pkg(array $files = ['app/Test.php' => '<?php // ok'], array $manifest = [], ?callable $zipHook = null, bool $sign = true, ?string $secret = null): string
    {
        $m = array_merge(['format' => 1, 'app' => 'elgarom', 'key_id' => 'test-key', 'version' => '1.0.1', 'min_from' => '1.0.0', 'php_min' => '8.3.0',
            'files' => array_map(fn ($c) => hash('sha256', $c), $files), 'delete' => []], $manifest);
        $json = json_encode($m);
        $path = $this->dir.'/p'.uniqid().'.zip';
        $z = new ZipArchive;
        $z->open($path, ZipArchive::CREATE);
        $z->addFromString('manifest.json', $json);
        if ($sign) {
            $z->addFromString('manifest.sig', base64_encode(sodium_crypto_sign_detached($json, $secret ?? $this->secret)));
        }
        foreach ($files as $rel => $c) {
            $z->addFromString('files/'.$rel, $c);
        }
        $zipHook && $zipHook($z);
        $z->close();

        return $path;
    }

    private function rejects(string $path, string $needle, string $current = '1.0.0'): void
    {
        try {
            (new PackageVerifier)->verify($path, $current);
            $this->fail('Pachetul ar fi trebuit respins: '.$needle);
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString($needle, $e->getMessage());
        }
    }

    public function test_valid_package_passes(): void
    {
        $m = (new PackageVerifier)->verify($this->pkg(), '1.0.0');
        $this->assertSame('1.0.1', $m['version']);
        $this->assertSame('test-key', $m['_key_id']);
    }

    public function test_unsigned_is_rejected(): void
    {
        $this->rejects($this->pkg(sign: false), 'nu este semnat');
    }

    public function test_foreign_key_is_rejected(): void
    {
        $this->rejects($this->pkg(secret: sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair())), 'Semnătura nu este validă');
    }

    public function test_modified_file_is_rejected(): void
    {
        $this->rejects($this->pkg(zipHook: fn ($z) => $z->addFromString('files/app/Test.php', '<?php evil();')), 'modificat după semnare');
    }

    public function test_undeclared_file_is_rejected(): void
    {
        $this->rejects($this->pkg(zipHook: fn ($z) => $z->addFromString('files/app/Extra.php', '<?php')), 'nedeclarat');
    }

    public function test_path_traversal_is_rejected(): void
    {
        $this->rejects($this->pkg(zipHook: fn ($z) => $z->addFromString('files/../../evil.php', 'x')), 'Cale nepermisă');
        $this->rejects($this->pkg(['../config/x.php' => 'x']), 'Cale nepermisă');
    }

    public function test_symlink_is_rejected(): void
    {
        $this->rejects($this->pkg(zipHook: function ($z) {
            $z->addFromString('files/app/link', '/etc/passwd');
            $z->setExternalAttributesName('files/app/link', ZipArchive::OPSYS_UNIX, (0120777 << 16));
        }), 'link simbolic');
    }

    public function test_protected_paths_are_rejected(): void
    {
        $this->rejects($this->pkg(['.env' => 'APP_KEY=x']), 'protejată');
        $this->rejects($this->pkg(['storage/app/private/x.txt' => 'x']), 'protejată');
        $this->rejects($this->pkg(['public/media/x.jpg' => 'x']), 'protejată');
        $this->rejects($this->pkg(manifest: ['delete' => ['.env']]), 'protejată');
    }

    public function test_versions_are_enforced(): void
    {
        $this->rejects($this->pkg(manifest: ['version' => '1.0.0']), 'nu este mai nouă', '1.0.0');
        $this->rejects($this->pkg(manifest: ['version' => '2.0.0', 'min_from' => '1.5.0']), 'cel puțin versiunea 1.5.0', '1.0.0');
        $this->rejects($this->pkg(manifest: ['php_min' => '99.0.0']), 'necesită PHP');
    }

    public function test_oversized_package_is_rejected(): void
    {
        config(['elgarom.updates.max_uncompressed_bytes' => 10]);
        $this->rejects($this->pkg(['app/Big.php' => str_repeat('a', 100)]), 'limita admisă');
    }
}
