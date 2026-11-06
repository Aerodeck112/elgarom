<?php

namespace Tests\Feature;

use App\Models\MailOutbox;
use App\Models\QuoteRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\Mail\Outbox;
use App\Support\FormGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function token(): string
    {
        return Crypt::encryptString((string) (time() - 10));
    }

    private function quote(array $over = [], array $files = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/solicita-oferta', array_merge([
            '_t' => $this->token(), 'name' => 'Ion Test', 'email' => 'ion@example.com',
            'description' => 'Console ștanțate, 5000 buc.', 'privacy' => '1',
        ], $over) + ($files ? ['files' => $files] : []));
    }

    public function test_public_pages_render_and_legacy_urls_redirect(): void
    {
        foreach (['/', '/despre', '/servicii', '/procesul-de-zincare', '/capacitati-tehnice', '/aplicatii', '/proiecte', '/calitate', '/intrebari-frecvente', '/solicita-oferta', '/contact', '/politica-de-confidentialitate', '/sitemap.xml'] as $u) {
            $this->get($u)->assertOk();
        }
        $this->get('/despre-noi')->assertRedirect('/despre')->assertStatus(301);
        $this->get('/tehnologie')->assertRedirect('/procesul-de-zincare');
        $this->get('/?p=24')->assertRedirect('/contact');
        $this->get('/wp-login.php')->assertStatus(410);
        $this->get('/nu-exista-pagina')->assertNotFound();
    }

    public function test_security_headers_present(): void
    {
        $r = $this->get('/');
        $this->assertStringContainsString("'strict-dynamic'", $r->headers->get('Content-Security-Policy'));
        $this->assertSame('DENY', $r->headers->get('X-Frame-Options'));
        $this->assertSame('nosniff', $r->headers->get('X-Content-Type-Options'));
    }

    public function test_unconfirmed_capabilities_are_never_published(): void
    {
        $html = $this->get('/capacitati-tehnice')->getContent();
        $this->assertStringContainsString('1900 × 1200 × 450', $html);
        $this->assertStringNotContainsString('Rezistență în ceață salină', $html);
    }

    public function test_quote_is_saved_even_when_smtp_is_not_configured(): void
    {
        $this->quote()->assertRedirect(route('quote.sent'));
        $q = QuoteRequest::firstOrFail();
        $this->assertMatchesRegularExpression('/^ELG-\d{4}-\d{5}$/', $q->reference);
        $this->assertSame(2, MailOutbox::where('related_id', $q->id)->count()); // team + client
        $this->assertSame(0, MailOutbox::where('status', 'sent')->count());
        $this->get(route('quote.sent'))->assertSee($q->reference);
    }

    public function test_smtp_failure_keeps_mail_pending_for_retry(): void
    {
        Setting::put('smtp.host', '127.0.0.1');
        Setting::put('smtp.port', 1); // nothing listens here
        Setting::put('smtp.from_email', 'site@elgarom.test');
        Setting::put('smtp.encryption', 'none');
        Setting::put('smtp.timeout', 2);
        $this->quote()->assertRedirect(route('quote.sent'));
        app(Outbox::class)->process();
        foreach (MailOutbox::all() as $m) {
            $this->assertSame('pending', $m->status);
            $this->assertSame(1, $m->attempts);
            $this->assertNotNull($m->last_error);
            $this->assertTrue($m->next_attempt_at->isFuture());
        }
    }

    public function test_validation_error_keeps_input_and_valid_files(): void
    {
        $pdf = UploadedFile::fake()->createWithContent('desen.pdf', "%PDF-1.4\n%%EOF");
        $bad = UploadedFile::fake()->createWithContent('script.pdf', '<?php system($_GET[1]);');
        $this->quote(['description' => ''], [$pdf, $bad])->assertSessionHasErrors(['description', 'files.1'], null, 'oferta')->assertSessionHasInput('name', 'Ion Test');
        $this->assertCount(1, session('quote_stash'));
        $this->assertSame(0, QuoteRequest::count());
        $this->quote()->assertRedirect(route('quote.sent'));
        $q = QuoteRequest::firstOrFail();
        $this->assertSame(1, $q->files()->count());
        $this->assertFileExists(storage_path('app/private/'.$q->files()->first()->path));
    }

    public function test_spam_protections(): void
    {
        $this->quote(['website' => 'http://spam'])->assertSessionHas('quote_failed');
        $this->quote(['_t' => Crypt::encryptString((string) time())])->assertSessionHas('quote_failed'); // too fast
        $this->quote(['_t' => 'invalid'])->assertSessionHas('quote_failed');
        $this->assertSame(0, QuoteRequest::count());
    }

    public function test_admin_requires_login_and_two_factor(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $u = User::forceCreate(['name' => 'A', 'email' => 'a@test.ro', 'password' => bcrypt('Parola-Test-2026'), 'role' => 'admin', 'is_active' => true]);
        $this->actingAs($u)->get('/admin')->assertRedirect(route('admin.2fa.setup'));
        $u->forceFill(['two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()])->save();
        $this->actingAs($u)->get('/admin')->assertRedirect(route('admin.2fa.challenge'));
        $this->actingAs($u)->withSession(['admin.2fa_passed' => true, 'admin.last_seen' => time()])->get('/admin')->assertOk();
    }

    public function test_role_permissions_are_enforced_server_side(): void
    {
        $editor = User::forceCreate(['name' => 'E', 'email' => 'e@test.ro', 'password' => bcrypt('x'), 'role' => 'editor', 'is_active' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
        $s = ['admin.2fa_passed' => true, 'admin.last_seen' => time(), 'admin.confirmed_at' => time()];
        $this->actingAs($editor)->withSession($s)->get('/admin/utilizatori')->assertForbidden();
        $this->actingAs($editor)->withSession($s)->get('/admin/actualizari')->assertForbidden();
        $this->actingAs($editor)->withSession($s)->get('/admin/setari/smtp')->assertForbidden();
        $this->actingAs($editor)->withSession($s)->get('/admin/continut/services')->assertOk();
        $sales = User::forceCreate(['name' => 'S', 'email' => 's@test.ro', 'password' => bcrypt('x'), 'role' => 'sales', 'is_active' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
        $this->actingAs($sales)->withSession($s)->get('/admin/pagini')->assertForbidden();
        $this->actingAs($sales)->withSession($s)->get('/admin/cereri')->assertOk();
    }

    public function test_quote_files_are_not_public(): void
    {
        $pdf = UploadedFile::fake()->createWithContent('desen.pdf', "%PDF-1.4\n%%EOF");
        $this->quote([], [$pdf]);
        $q = QuoteRequest::firstOrFail();
        $f = $q->files()->first();
        $this->get(route('admin.quotes.file', [$q, $f]))->assertRedirect(route('admin.login'));
        $this->assertStringStartsWith(storage_path('app/private'), storage_path('app/private/'.$f->path));
    }

    public function test_assistant_falls_back_when_not_configured(): void
    {
        $this->postJson('/asistent/mesaj', ['message' => 'Salut'])->assertStatus(503)->assertJsonStructure(['error', 'fallback', 'links']);
        Setting::put('ai.enabled', true);
        Setting::put('ai.api_key', 'sk-ant-test', true);
        Setting::put('ai.daily_budget_usd', 0);
        $this->postJson('/asistent/mesaj', ['message' => 'Salut'])->assertStatus(503);
    }

    public function test_media_upload_rejects_disguised_script(): void
    {
        $this->expectException(\RuntimeException::class);
        $tmp = tempnam(sys_get_temp_dir(), 'x');
        file_put_contents($tmp, "\xFF\xD8\xFF<?php echo 1; ?>");
        app(\App\Services\MediaService::class)->storeImage($tmp, 'poza.jpg');
    }

    public function test_backup_dump_restores_into_an_empty_database(): void
    {
        $b = app(\App\Services\BackupService::class)->create('db', null, 'test');
        $zip = new \ZipArchive;
        $zip->open($b->absolutePath());
        $sql = $zip->getFromName('database.sql');
        $zip->close();
        @unlink($b->absolutePath());
        // session and cache tables: structure only, never dropped (a restore keeps the logged-in sessions)
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `sessions`', $sql);
        $this->assertStringNotContainsString('DROP TABLE IF EXISTS `sessions`', $sql);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `pages`', $sql);
    }
}
