<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IntegrityTest extends TestCase
{
    use RefreshDatabase;

    /** Code taken over from the earlier website must not point at classes that were left behind. */
    public function test_application_code_references_only_existing_classes(): void
    {
        $missing = [];
        $files = array_merge(
            iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path(), \FilesystemIterator::SKIP_DOTS))),
            array_map(fn ($f) => new \SplFileInfo(base_path($f)), ['routes/console.php', 'routes/admin.php', 'routes/web.php', 'install.php']),
        );
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            preg_match_all('/\\\\?App\\\\((?:[A-Z][A-Za-z0-9]*\\\\)*[A-Z][A-Za-z0-9]*)/', file_get_contents($file->getPathname()), $m);
            foreach (array_unique($m[1]) as $class) {
                $path = app_path(str_replace('\\', '/', $class));
                if (! is_file($path.'.php') && ! is_dir($path)) {
                    $missing[] = basename($file->getPathname()).' → App\\'.$class;
                }
            }
        }
        $this->assertSame([], $missing);
    }

    public function test_a_user_can_be_deleted(): void
    {
        $this->signIn();
        $other = new User(['name' => 'De șters', 'email' => 'sters@example.test', 'role' => 'viewer', 'is_active' => true]);
        $other->forceFill(['password' => Hash::make('parola-de-test-123')])->save();
        $this->delete("/crm/utilizatori/{$other->id}")->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $other->id]);
    }
}
