<?php

namespace Tests\Unit\Calc;

use App\Calc\Engine\CalcException;
use App\Calc\Engine\ExcelError;
use App\Calc\Engine\FormulaParser;
use App\Calc\Engine\Workbook;
use PHPUnit\Framework\TestCase;

/** Excel semantics of the formula engine, on a small hand-made model (no client data). */
class EngineTest extends TestCase
{
    private function wb(array $cells, array $names = []): Workbook
    {
        $rows = [];
        $maxR = $maxC = 1;
        foreach ($cells as $addr => $v) {
            preg_match('/^([A-Z]+)(\d+)$/', $addr, $m);
            $c = FormulaParser::colToNum($m[1]);
            $r = (int) $m[2];
            $rows[$r][$c] = is_string($v) && str_starts_with($v, '=') ? ['f' => substr($v, 1), 'v' => null] : (is_int($v) ? (float) $v : $v);
            $maxR = max($maxR, $r);
            $maxC = max($maxC, $c);
        }

        return Workbook::fromModel(['sheets' => [['name' => 'S', 'cells' => $rows, 'maxRow' => $maxR, 'maxCol' => $maxC], ['name' => '2 P', 'cells' => [1 => [1 => 7.0]], 'maxRow' => 1, 'maxCol' => 1]], 'names' => $names]);
    }

    private function calc(string $formula, array $cells = [], array $names = []): mixed
    {
        return $this->wb($cells + ['Z99' => '='.$formula], $names)->get('S', 'Z99');
    }

    public function test_arithmetic_precedence_and_coercion(): void
    {
        $this->assertSame(14.0, $this->calc('2+3*4'));
        $this->assertSame(4.0, $this->calc('-2^2'));
        $this->assertSame(64.0, $this->calc('2^3^2'));
        $this->assertSame(0.5, $this->calc('50%'));
        $this->assertSame(3.0, $this->calc('"2"+TRUE'));
        $this->assertSame('#VALUE!', (string) $this->calc('"abc"+1'));
        $this->assertSame('#DIV/0!', (string) $this->calc('1/0'));
        $this->assertSame(7.0, $this->calc("'2 P'!A1"));
    }

    public function test_empty_cells_and_comparisons(): void
    {
        $this->assertTrue($this->calc('A1=""'));
        $this->assertTrue($this->calc('A1=0'));
        $this->assertFalse($this->calc('""=0'));
        $this->assertTrue($this->calc('"abc"="ABC"'));
        $this->assertTrue($this->calc('"a">5'));
        $this->assertTrue($this->calc('(0.1+0.2)=0.3'));
        $this->assertSame(0.0, $this->calc('A1'));
    }

    public function test_text_functions(): void
    {
        $this->assertSame('Șta', $this->calc('LEFT("Ștanță",3)'));
        $this->assertSame(6.0, $this->calc('LEN("Ștanță")'));
        $this->assertSame('a 1.5', $this->calc('"a "&1.5'));
        $this->assertSame('0.333333333333333', $this->calc('""&1/3'));
        $this->assertSame('12.50', $this->calc('TEXT(12.5,"0.00")'));
        $this->assertSame('25%', $this->calc('TEXT(0.25,"0%")'));
        $this->assertSame('1,234.6', $this->calc('FIXED(1234.56,1)'));
        $this->assertSame(1.5, $this->calc('VALUE(SUBSTITUTE("1,5",",","."))'));
        $this->assertSame(3.0, $this->calc('FIND("c","abcabc")'));
        $this->assertSame('#VALUE!', (string) $this->calc('FIND("x","abc")'));
        $this->assertSame('a b', $this->calc('TRIM("  a   b ")'));
    }

    public function test_rounding(): void
    {
        $this->assertSame(2.5, $this->calc('ROUND(2.45,1)'));
        $this->assertSame(-3.0, $this->calc('ROUND(-2.5,0)'));
        $this->assertSame(3.0, $this->calc('ROUNDUP(2.01,0)'));
        $this->assertSame(3.0, $this->calc('ROUNDUP(0.3/0.1,0)'));
        $this->assertSame(-3.0, $this->calc('INT(-2.1)'));
    }

    public function test_lookups(): void
    {
        $cells = ['A1' => 'oțel', 'B1' => 10, 'A2' => 'inox', 'B2' => 20, 'A3' => 'alu', 'B3' => 30, 'D1' => 1, 'D2' => 5, 'D3' => 9];
        $this->assertSame(20.0, $this->calc('VLOOKUP("INOX",A1:B3,2,FALSE)', $cells));
        $this->assertSame('#N/A', (string) $this->calc('VLOOKUP("x",A1:B3,2,FALSE)', $cells));
        $this->assertSame(3.0, $this->calc('MATCH("alu",A1:A3,0)', $cells));
        $this->assertSame(2.0, $this->calc('MATCH(6,D1:D3)', $cells));
        $this->assertSame(30.0, $this->calc('INDEX(B1:B3,3)', $cells));
        $this->assertSame(60.0, $this->calc('SUM(INDEX(B1:B3,1):INDEX(B1:B3,3))', $cells));
        $this->assertSame(20.0, $this->calc('LOOKUP(6,D1:D3,B1:B3)', $cells));
        $this->assertSame(30.0, $this->calc('SUM(LISTA)', $cells + ['E1' => 30], ['LISTA' => 'S!$E$1:$E$2']));
    }

    public function test_conditional_aggregates(): void
    {
        $cells = ['A1' => 'Da', 'B1' => 10, 'A2' => 'Nu', 'B2' => 20, 'A3' => 'Da', 'B3' => 30, 'A4' => '=""', 'B4' => 'text'];
        $this->assertSame(40.0, $this->calc('SUMIFS(B1:B4,A1:A4,"Da")', $cells));
        $this->assertSame(2.0, $this->calc('COUNTIF(B1:B4,">15")', $cells));
        $this->assertSame(4.0, $this->calc('COUNTIF(A1:A4,"<>")', $cells));
        $this->assertSame(20.0, $this->calc('AVERAGEIFS(B1:B4,A1:A4,"D*")', $cells));
        $this->assertSame(30.0, $this->calc('MAXIFS(B1:B4,A1:A4,"Da")', $cells));
        $this->assertSame(40.0, $this->calc('SUMPRODUCT((A1:A4="Da")*1,B1:B4)', $cells));
        $this->assertSame(60.0, $this->calc('SUM(B1:B4)', $cells));
        $this->assertSame(3.0, $this->calc('COUNT(B1:B4)', $cells));
        $this->assertSame(4.0, $this->calc('COUNTA(A1:A4)', $cells));
        $this->assertSame(20.0, $this->calc('AGGREGATE(15,6,B1:B3/(A1:A3="Nu"),1)', $cells));
    }

    public function test_errors_propagate_and_are_caught(): void
    {
        $this->assertSame('x', $this->calc('IFERROR(1/0,"x")'));
        $this->assertSame('#N/A', (string) $this->calc('1+MATCH("z",A1:A2,0)'));
        $this->assertTrue($this->calc('ISNA(MATCH("z",A1:A2,0))'));
        $this->assertSame('#NAME?', (string) $this->calc('NUME_INEXISTENT+1'));
        $this->assertInstanceOf(ExcelError::class, $this->calc('SUM(A1:A2)', ['A1' => '=1/0']));
    }

    public function test_inputs_recalculate_and_circular_references_are_rejected(): void
    {
        $wb = $this->wb(['A1' => 2, 'A2' => '=A1*10', 'A3' => '=A2+1']);
        $this->assertSame(21.0, $wb->get('S', 'A3'));
        $wb->set('S', 'A1', 5);
        $this->assertSame(51.0, $wb->get('S', 'A3'));
        // A user-supplied text is a constant, never a formula.
        $wb->set('S', 'A1', '=1+1');
        $this->assertSame('#VALUE!', (string) $wb->get('S', 'A2'));

        $this->expectException(CalcException::class);
        $this->wb(['A1' => '=A2', 'A2' => '=A1'])->get('S', 'A1');
    }

    public function test_unsupported_functions_are_reported_not_guessed(): void
    {
        $wb = $this->wb(['A1' => '=XLOOKUP(1,B1:B2,C1:C2)']);
        $this->assertSame(['XLOOKUP'], $wb->unsupportedFunctions());
        $this->expectException(CalcException::class);
        $wb->get('S', 'A1');
    }
}
