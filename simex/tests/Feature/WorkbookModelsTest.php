<?php

namespace Tests\Feature;

use App\Calc\CalcSession;
use App\Calc\Engine\ExcelError;
use App\Calc\Engine\Workbook;
use App\Calc\Engine\XlsxModelReader;
use App\Calc\ModelRegistry;
use App\Calc\ModelValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Compares the engine with the original workbooks. The workbooks hold confidential tariffs and are not in the
 * repository: place them in storage/app/private/calc-models/source to run these tests (they are skipped otherwise).
 */
class WorkbookModelsTest extends TestCase
{
    public static function models(): array
    {
        return [['rectificare-okamoto'], ['frezare-genos-m460'], ['strunjire-genos-l3000'], ['electroeroziune-cut-e600'], ['laser-abkant-finisaje'], ['stante-matrite-fepo']];
    }

    #[DataProvider('models')]
    public function test_every_formula_matches_the_result_stored_by_excel(string $key): void
    {
        $def = app(ModelRegistry::class)->find($key);
        $file = $this->workbookDir().'/'.$def->file_hint;
        if (! is_file($file)) {
            $this->markTestSkipped('Workbook-ul sursă nu este disponibil: '.$def->file_hint);
        }
        $report = (new ModelValidator)->validate($file, $def);
        $this->assertSame([], $report['errors']);
        $this->assertGreaterThan(300, $report['formulas']);
        $this->assertSame(0, $report['mismatches']);
    }

    /** Scenario workbooks with filled-in inputs, recalculated by LibreOffice: an independent second reference. */
    public function test_filled_in_scenarios_match_an_independent_spreadsheet_engine(): void
    {
        $files = glob(storage_path('app/private/calc-models/golden/*.xlsx')) ?: [];
        if ($files === []) {
            $this->markTestSkipped('Scenariile de referință nu sunt disponibile.');
        }
        $checked = 0;
        foreach ($files as $file) {
            $fill = str_contains($file, 'CUT_E') ? 'FFFFF2CC' : null;
            $wb = Workbook::fromModel((new XlsxModelReader)->read($file, $fill));
            $wb->today = 46332.0;
            foreach ($wb->formulaCells() as [$sheet, $addr, $cached]) {
                $v = $wb->get($sheet, $addr);
                $cached = is_array($cached) ? ExcelError::of($cached['#']) : $cached;
                $same = $cached === null ? ($v === '' || $v === null) : (is_float($cached) && is_float($v) ? abs($cached - $v) <= 1e-9 * max(1.0, abs($cached)) : $cached === $v);
                $this->assertTrue($same, basename($file)." {$sheet}!{$addr}");
                $checked++;
            }
        }
        $this->assertGreaterThan(30000, $checked);
    }

    public function test_only_input_cells_of_permitted_sheets_can_be_written(): void
    {
        $def = app(ModelRegistry::class)->find('rectificare-okamoto');
        $file = $this->workbookDir().'/'.$def->file_hint;
        if (! is_file($file)) {
            $this->markTestSkipped('Workbook-ul sursă nu este disponibil.');
        }
        $session = new CalcSession($def, (new XlsxModelReader)->read($file));
        $rejected = $session->apply(['Calculator!B8' => '200', 'Calculator!B46' => 1, 'Parametri!B24' => 99, 'Motor!B5' => 1, 'Inexistent!A1' => 1, 'fara-foaie' => 1], ['input']);
        $this->assertEqualsCanonicalizing(['Calculator!B46', 'Parametri!B24', 'Motor!B5', 'Inexistent!A1', 'fara-foaie'], $rejected);
        $this->assertSame([], $session->apply(['Parametri!B24' => 3], ['input', 'params']));
        // A blocked calculation never reports hours.
        $this->assertSame('block', $session->summary()['standard']['state']);
        $this->assertNull($session->summary()['standard']['hours']);
    }
}
