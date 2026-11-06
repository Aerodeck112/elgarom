<?php

namespace Tests\Feature;

use App\Models\MailOutbox;
use App\Models\Page;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Support\TranslationImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class LocalesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): self
    {
        $u = User::forceCreate(['name' => 'A', 'email' => 'a@test.ro', 'password' => bcrypt('x'), 'role' => 'admin', 'is_active' => true,
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);

        return $this->actingAs($u)->withSession(['admin.2fa_passed' => true, 'admin.last_seen' => time(), 'admin.confirmed_at' => time()]);
    }

    public function test_every_language_has_its_pages_with_hreflang(): void
    {
        $pages = [
            'de' => ['/de', '/de/ueber-uns', '/de/leistungen', '/de/verzinkungsprozess', '/de/technische-daten', '/de/angebot-anfordern', '/de/kontakt', '/de/datenschutz', '/de/leistungen/gestellverzinkung'],
            'en' => ['/en', '/en/about', '/en/services', '/en/zinc-plating-process', '/en/technical-capabilities', '/en/request-a-quote', '/en/contact', '/en/privacy-policy', '/en/services/rack-zinc-plating'],
            'hu' => ['/hu', '/hu/rolunk', '/hu/szolgaltatasok', '/hu/horganyzasi-folyamat', '/hu/muszaki-adatok', '/hu/ajanlatkeres', '/hu/kapcsolat', '/hu/adatvedelem', '/hu/szolgaltatasok/fuggesztett-horganyzas'],
        ];
        foreach ($pages as $loc => $urls) {
            foreach ($urls as $u) {
                $html = $this->get($u)->assertOk()->getContent();
                $this->assertStringContainsString('<html lang="'.$loc.'"', $html, $u);
                $this->assertStringContainsString('hreflang="ro"', $html, $u);
                $this->assertStringContainsString('hreflang="x-default"', $html, $u);
            }
        }
        $ro = $this->get('/despre')->getContent();
        foreach (['/de/ueber-uns', '/en/about', '/hu/rolunk'] as $alt) {
            $this->assertStringContainsString('hreflang="'.substr($alt, 1, 2).'" href="'.url($alt).'"', $ro);
        }
        $this->assertStringContainsString('Angebot anfordern', $this->get('/de')->getContent());
        $this->assertStringContainsString('Elolvastam az', $this->get('/hu/ajanlatkeres')->getContent());
        $this->assertStringContainsString('Gestellteile bis 1900 × 1200 × 450 mm', Setting::get('seo.default_description.de'));
    }

    public function test_technical_values_are_copied_not_invented(): void
    {
        $html = $this->get('/en/technical-capabilities')->assertOk()->getContent();
        $this->assertStringContainsString('1900 × 1200 × 450', $html);
        $this->assertStringNotContainsString('Rezistență în ceață salină', $html);
        $this->assertStringNotContainsString('salt spray', strtolower($html));
    }

    public function test_sitemap_lists_all_languages_with_alternates(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        foreach (['/de/kontakt', '/en/contact', '/hu/kapcsolat', '/contact'] as $u) {
            $this->assertStringContainsString('<loc>'.url($u).'</loc>', $xml);
        }
        $this->assertStringContainsString('xhtml:link rel="alternate" hreflang="hu"', $xml);
    }

    public function test_disabled_language_returns_404_and_leaves_switcher(): void
    {
        Setting::put('locales.enabled', ['ro', 'de', 'en']);
        $this->get('/hu')->assertNotFound();
        $this->get('/hu/kapcsolat')->assertNotFound();
        $html = $this->get('/de')->assertOk()->getContent();
        $this->assertStringNotContainsString('hreflang="hu"', $html);
        $this->assertStringNotContainsString('/hu/kapcsolat', $this->get('/sitemap.xml')->getContent());
    }

    public function test_quote_from_german_page_confirms_in_german(): void
    {
        $r = $this->post('/solicita-oferta', [
            '_t' => Crypt::encryptString((string) (time() - 10)), '_locale' => 'de', 'name' => 'Hans Test', 'email' => 'hans@example.com',
            'description' => 'Gestanzte Konsolen, 5000 Stück.', 'privacy' => '1',
        ]);
        $r->assertRedirect(url('/de/anfrage-gesendet'));
        $q = QuoteRequest::latest('id')->first();
        $this->assertSame('de', $q->locale);
        $mail = MailOutbox::where('to_email', 'hans@example.com')->latest('id')->first();
        $this->assertStringContainsString($q->reference, $mail->subject);
        $this->get('/de/anfrage-gesendet')->assertOk()->assertSee('Ihre Anfrage wurde registriert');
    }

    public function test_validation_messages_follow_the_page_language(): void
    {
        $r = $this->from('/en/request-a-quote')->post('/solicita-oferta', ['_t' => Crypt::encryptString((string) (time() - 10)), '_locale' => 'en', 'privacy' => '1']);
        $r->assertSessionHasErrors(['name' => 'Please enter your name.'], null, 'oferta');
    }

    public function test_importer_is_idempotent_and_keeps_admin_edits(): void
    {
        $p = Page::where('locale', 'de')->where('translation_key', 'despre')->first();
        $p->update(['title' => 'Von Hand geändert']);
        $before = [Page::count(), Service::count()];
        (new TranslationImporter)->run();
        $this->assertSame($before, [Page::count(), Service::count()]);
        $this->assertSame('Von Hand geändert', $p->fresh()->title);
    }

    public function test_admin_manages_content_per_language(): void
    {
        $a = $this->admin();
        $a->get('/admin/pagini?locale=hu')->assertOk()->assertSee('Az Elgaromról');
        $a->get('/admin/continut/services?locale=en')->assertOk()->assertSee('Rack zinc plating');
        $a->get('/admin/continut/faqs?locale=de')->assertOk();
        $a->get('/admin/setari/languages')->assertOk()->assertSee('Magyar');
        $a->get('/admin')->assertOk()->assertSee('Traduceri de verificat');

        // new Romanian page, then its English translation (draft, same sections)
        $a->post('/admin/pagini', ['title' => 'Industria auto', 'slug' => 'industria-auto', 'template' => 'standard', 'locale' => 'ro'])->assertRedirect();
        $ro = Page::where('slug', 'industria-auto')->firstOrFail();
        $this->assertSame('industria-auto', $ro->translation_key);
        $a->post('/admin/pagini/'.$ro->id.'/traduce', ['locale' => 'en'])->assertRedirect();
        $en = Page::where('locale', 'en')->where('translation_key', 'industria-auto')->firstOrFail();
        $this->assertSame('draft', $en->status);
        $a->get('/admin/pagini/'.$en->id)->assertOk()->assertSee('Română');

        // translation of a service through the resource form
        $roService = Service::where('locale', 'ro')->first();
        $a->get('/admin/continut/services/nou?locale=hu&from='.$roService->id)->assertOk()->assertSee($roService->title);
        $hu = Service::where('locale', 'hu')->where('translation_key', $roService->translation_key)->first();
        $hu->delete();
        $a->post('/admin/continut/services', ['locale' => 'hu', 'translation_key' => $roService->translation_key, 'title' => 'Új szolgáltatás', 'slug' => '', 'is_published' => '1'])->assertRedirect();
        $this->assertSame($roService->translation_key, Service::where('locale', 'hu')->where('title', 'Új szolgáltatás')->value('translation_key'));

        // disabling a language from the settings
        $a->put('/admin/setari/languages', ['locales__on__de' => '1', 'locales__on__en' => '1'])->assertRedirect();
        $this->assertSame(['ro', 'de', 'en'], Setting::get('locales.enabled'));
    }
}
