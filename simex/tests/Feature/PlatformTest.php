<?php

namespace Tests\Feature;

use App\Models\CalcModelVersion;
use App\Models\Calculation;
use App\Models\Entity;
use App\Models\Machine;
use App\Models\Quote;
use App\Models\Setting;
use App\Services\Quotes\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_public_site_shows_the_group_and_hides_the_platform(): void
    {
        $this->get('/')->assertOk()->assertSee('GRUP SIMEX SRL')->assertSee('ARGENTO GROUP')->assertSee('FEPO STEEL ENGINEERING')->assertSee('ELGAROM')
            ->assertDontSee('CUI')->assertHeader('Content-Security-Policy');
        $this->get('/crm')->assertRedirect(route('admin.login'));
        $this->get('/crm/oferte')->assertRedirect(route('admin.login'));
        $this->get('/robots.txt')->assertSee('Disallow: /');
        $this->get('/confidentialitate')->assertNotFound();
    }

    public function test_two_factor_is_required_for_every_role(): void
    {
        $user = $this->signIn('viewer');
        $this->withSession(['admin.2fa_passed' => false])->get('/crm')->assertRedirect(route('admin.2fa.challenge'));
        $user->forceFill(['two_factor_confirmed_at' => null])->save();
        $this->withSession(['admin.2fa_passed' => true])->get('/crm')->assertRedirect(route('admin.2fa.setup'));
    }

    public function test_main_screens_render_for_a_super_administrator(): void
    {
        $this->signIn();
        $company = $this->company();
        foreach (['/crm', '/crm/companii', '/crm/companii/noua', "/crm/companii/{$company->id}", '/crm/cereri', '/crm/cereri/noua', '/crm/oferte', '/crm/oferte/noua', '/crm/calculatoare/piese',
            '/crm/calculatoare/stante', '/crm/stante', '/crm/stante/nou', '/crm/zincare', '/crm/zincare/nou', '/crm/modele-excel', '/crm/biblioteca/materials', '/crm/biblioteca/machines',
            '/crm/biblioteca/operations', '/crm/biblioteca/suppliers', '/crm/biblioteca/products', '/crm/biblioteca/materials/nou', '/crm/grup', '/crm/setari/general', '/crm/setari/smtp',
            '/crm/setari/ai', '/crm/setari/site', '/crm/modul/prospectare', '/crm/modul/marketing', '/crm/utilizatori', '/crm/jurnal', '/crm/backup', '/crm/actualizari', '/crm/securitate', '/crm/cont'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/crm/calculator/rectificare-okamoto')->assertOk()->assertSee('Formulă în curs de configurare');
    }

    public function test_roles_are_isolated(): void
    {
        $company = $this->company();
        $this->signIn('viewer');
        $this->get('/crm/companii')->assertOk();
        $this->get('/crm/companii/noua')->assertForbidden();
        $this->post('/crm/companii', ['name' => 'X'])->assertForbidden();
        $this->delete("/crm/companii/{$company->id}")->assertForbidden();
        $this->get('/crm/calculatoare/piese')->assertForbidden();
        $this->get('/crm/utilizatori')->assertForbidden();
        $this->get('/crm/setari/ai')->assertForbidden();
        $this->get('/crm/actualizari')->assertForbidden();
        $this->get('/crm/modele-excel')->assertForbidden();

        $this->signIn('marketing');
        $this->get('/crm/oferte')->assertForbidden();
        $this->get('/crm/modul/marketing')->assertOk();

        $this->signIn('admin');
        $this->get('/crm/actualizari')->assertForbidden();
        $this->get('/crm/utilizatori')->assertOk();
    }

    public function test_tariffs_can_only_be_changed_with_the_rates_permission(): void
    {
        $machine = Machine::first();
        $this->signIn('inginer_ofertare');
        $payload = ['code' => $machine->code, 'name' => $machine->name, 'currency' => 'EUR', 'hourly_rate' => '55,5'];
        $this->put("/crm/biblioteca/machines/{$machine->id}", $payload)->assertRedirect();
        $this->assertNull($machine->fresh()->hourly_rate);

        $this->signIn('admin');
        $this->put("/crm/biblioteca/machines/{$machine->id}", $payload)->assertRedirect();
        $this->assertSame(55.5, (float) $machine->fresh()->hourly_rate);
        $this->assertDatabaseHas('price_history', ['priceable_id' => $machine->id, 'field' => 'hourly_rate']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'tarif.modificat']);
    }

    private function quote(array $items, array $attrs = []): Quote
    {
        $quote = Quote::create($attrs + ['number' => 'OT-TEST-'.uniqid(), 'entity_id' => $this->issuer()->id, 'company_id' => $this->company()->id, 'currency' => 'EUR', 'vat_rate' => 21, 'discount_percent' => 0]);
        foreach ($items as $i => $item) {
            $quote->items()->create($item + ['position' => $i + 1, 'name' => 'Poziția '.($i + 1)]);
        }

        return app(QuoteService::class)->recalculate($quote)->fresh();
    }

    public function test_quote_totals_are_rounded_per_line_and_optional_lines_are_excluded(): void
    {
        $quote = $this->quote([
            ['quantity' => 3, 'unit_price' => 0.335, 'unit_cost' => 0.2],          // 1.005 → 1.01
            ['quantity' => 1000, 'unit_price' => 1.2345, 'discount_percent' => 10, 'unit_cost' => 0.9],   // 1111.05
            ['quantity' => 1, 'unit_price' => 500, 'is_optional' => true],
        ], ['discount_percent' => 5]);
        $this->assertSame(1112.06, $quote->subtotal);
        $this->assertSame(1056.46, $quote->total_net);
        $this->assertSame(221.86, $quote->vat_amount);
        $this->assertSame(1278.32, $quote->total_gross);
        $this->assertSame(900.6, $quote->cost_total);
        $this->assertSame(14.75, $quote->marginPercent());
    }

    public function test_margin_is_unknown_when_a_line_has_no_cost(): void
    {
        $quote = $this->quote([['quantity' => 1, 'unit_price' => 100, 'unit_cost' => 60], ['quantity' => 1, 'unit_price' => 50]]);
        $this->assertNull($quote->cost_total);
        $this->assertNull($quote->marginPercent());
    }

    public function test_quote_cannot_be_issued_by_an_entity_without_confirmed_legal_data(): void
    {
        $this->signIn('director_comercial');
        $quote = $this->quote([['quantity' => 2, 'unit_price' => 10, 'unit_cost' => 5]], ['entity_id' => $this->issuer(false)->id, 'status' => 'aprobare']);
        $this->post("/crm/oferte/{$quote->id}/aproba")->assertSessionHas('error');
        $this->assertSame('aprobare', $quote->fresh()->status);
        $this->assertNull($quote->fresh()->snapshot);
    }

    public function test_issued_quote_is_frozen_and_revisions_keep_history(): void
    {
        $this->signIn('agent_vanzari');
        $quote = $this->quote([['quantity' => 2, 'unit_price' => 10, 'unit_cost' => 5, 'code' => 'P-1']]);
        $this->post("/crm/oferte/{$quote->id}/aprobare")->assertSessionHas('status');
        $this->post("/crm/oferte/{$quote->id}/aproba")->assertForbidden();

        $this->signIn('director_comercial');
        $this->post("/crm/oferte/{$quote->id}/aproba")->assertSessionHas('status');
        $quote->refresh();
        $this->assertSame('aprobata', $quote->status);
        $this->assertSame(20.0, (float) $quote->snapshot['quote']['total_net']);
        $this->assertSame('Emitent Test SRL', $quote->snapshot['issuer']['legal_name']);
        $this->put("/crm/oferte/{$quote->id}", [])->assertStatus(409);

        // Later changes of master data do not touch the issued document.
        $quote->entity->update(['legal_name' => 'Altă Denumire SRL']);
        $this->assertSame('Emitent Test SRL', $quote->fresh()->snapshot['issuer']['legal_name']);
        $this->get("/crm/oferte/{$quote->id}/pdf")->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->post("/crm/oferte/{$quote->id}/revizie")->assertRedirect();
        $revision = Quote::where('number', $quote->number)->where('revision', 1)->firstOrFail();
        $this->assertSame('ciorna', $revision->status);
        $this->assertFalse($quote->fresh()->is_current);
        $this->assertSame('aprobata', $quote->fresh()->status);
        $this->get("/crm/oferte/{$quote->id}/compara/{$revision->id}")->assertOk();
    }

    public function test_internal_costs_and_margins_are_hidden_without_permission(): void
    {
        $quote = $this->quote([['quantity' => 2, 'unit_price' => 10, 'unit_cost' => 5.4321]]);
        $this->signIn('agent_vanzari');
        $this->get("/crm/oferte/{$quote->id}")->assertOk()->assertDontSee('5,4321')->assertDontSee('Marjă brută estimată');
        $this->get("/crm/oferte/{$quote->id}/excel?costuri=1")->assertOk();
        // The cost stored on the line survives an edit by a user who cannot see it.
        $this->put("/crm/oferte/{$quote->id}", ['company_id' => $quote->company_id, 'type' => 'piese', 'language' => 'ro', 'currency' => 'EUR', 'vat_rate' => '21',
            'items' => [['id' => $quote->items[0]->id, 'name' => 'Poziția 1', 'quantity' => '2', 'unit_price' => '12', 'unit_cost' => '0']]])->assertRedirect();
        $this->assertSame(5.4321, $quote->fresh()->items[0]->unit_cost);

        $this->signIn('director_comercial');
        $this->get("/crm/oferte/{$quote->id}")->assertSee('5,4321')->assertSee('Marjă brută estimată');
    }

    public function test_approval_reasons_follow_the_configured_thresholds(): void
    {
        Setting::put('quotes.min_margin', 30);
        Setting::put('quotes.max_discount', 5);
        $quote = $this->quote([['quantity' => 1, 'unit_price' => 100, 'unit_cost' => 80, 'discount_percent' => 10]]);
        $this->assertCount(2, app(QuoteService::class)->approvalReasons($quote));
    }

    public function test_calculator_computes_on_the_server_and_freezes_its_snapshot(): void
    {
        if (! is_file($this->workbookDir().'/Calculator_OKAMOTO_ore_manopera.xlsx')) {
            $this->markTestSkipped('Workbook-ul sursă nu este disponibil.');
        }
        $this->artisan('simex:modele', ['dir' => $this->workbookDir()])->assertSuccessful();
        $this->assertSame(count(glob($this->workbookDir().'/*.xlsx')), CalcModelVersion::where('status', 'activ')->count());
        $this->signIn('inginer_ofertare');
        $inputs = ['Calculator!B6' => 'S235/S355', 'Calculator!B8' => '200', 'Calculator!B9' => '150', 'Calculator!B10' => '30'];

        $res = $this->postJson('/crm/api/calculator/rectificare-okamoto', ['sheet' => 'Calculator', 'inputs' => $inputs + ['Motor!B5' => 9]])->assertOk()->json();
        $this->assertSame('ok', $res['summary']['standard']['state']);
        $this->assertEqualsWithDelta(1.831, $res['summary']['standard']['hours'], 0.001);
        $this->assertSame(['Motor!B5'], $res['rejected']);
        $this->assertNull($res['summary']['standard']['machine_cost_display']);
        // Parameter sheets need the rates permission, both to open and to change.
        $this->postJson('/crm/api/calculator/rectificare-okamoto', ['sheet' => 'Calculator', 'params' => ['Parametri!B24' => 9]])->assertForbidden();

        $this->post('/crm/calculator/rectificare-okamoto', ['title' => 'Placă test', 'inputs' => json_encode($inputs)])->assertRedirect();
        $calc = Calculation::firstOrFail();
        $this->assertEqualsWithDelta(1.831, $calc->hours, 0.001);
        $this->get('/crm/calculator/laser-abkant-finisaje')->assertOk()->assertSee('calc-config')->assertSee('2 · Configurare');
        $this->get("/crm/calcule/{$calc->id}")->assertOk()->assertSee($calc->reference);
        $this->assertNull($calc->machineCost());
        $this->assertSame(config('simex.engine_version'), $calc->results['engine_version']);

        // A tariff entered later does not change the saved calculation; a new copy picks it up.
        Machine::where('calc_model_key', 'rectificare-okamoto')->first()->update(['hourly_rate' => 40]);
        $this->assertNull($calc->fresh()->machineCost());
        $this->post("/crm/calcule/{$calc->id}/copie")->assertRedirect();
        $this->assertEqualsWithDelta(73.24, Calculation::latest('id')->first()->machineCost(), 0.05);
    }

    public function test_a_die_calculation_becomes_a_quote_in_one_step(): void
    {
        if (! is_file($this->workbookDir().'/model_calcul_matrite_stante_v35.xlsx')) {
            $this->markTestSkipped('Workbook-ul sursă nu este disponibil.');
        }
        $this->artisan('simex:modele', ['dir' => $this->workbookDir()])->assertSuccessful();
        Entity::where('code', 'fepo')->update(['legal_name' => 'FEPO TEST SRL', 'tax_id' => 'RO1', 'address' => 'Str. 1', 'legal_confirmed_at' => now()]);
        $company = $this->company();
        $this->signIn('inginer_ofertare');
        // Only input cells are accepted; the summary formula cannot be overwritten.
        $res = $this->postJson('/crm/api/calculator/stante-matrite-fepo', ['sheet' => 'Date proiect', 'inputs' => ['Date proiect!B11' => '2', 'Ofertă!C21' => 1]])->assertOk()->json();
        $this->assertSame(['Ofertă!C21'], $res['rejected']);
        $this->assertNotNull($res['summary']['oferta']['total']);
        // Known defect of the v35 workbook: Calcul general!H4:H13 turns rows without cost negative when the quantity is above 1.
        $this->assertNotEmpty(preg_grep('/^Valoare negativă/u', $res['summary']['oferta']['warnings']));
        $this->assertSame('warn', $res['summary']['oferta']['state']);

        $this->post('/crm/calculator/stante-matrite-fepo', ['title' => 'Ștanță test', 'company_id' => $company->id, 'inputs' => json_encode(['Date proiect!B11' => '2'])])->assertRedirect();
        $calc = Calculation::where('model_key', 'stante-matrite-fepo')->firstOrFail();
        $this->post("/crm/calcule/{$calc->id}/oferta")->assertRedirect();
        $quote = Quote::where('company_id', $company->id)->firstOrFail();
        $line = $quote->items()->firstOrFail();
        $this->assertSame('scula', $line->kind);
        $this->assertSame(2.0, $line->quantity);
        $this->assertSame('RON', $quote->currency);
        $this->assertEqualsWithDelta($calc->results['summary']['oferta']['total'], $line->quantity * $line->unit_price, 0.01);
        $this->assertNotNull($line->unit_cost);
        $this->get("/crm/calcule/{$calc->id}")->assertOk()->assertSee('Creează ofertă din acest calcul');
    }
}
