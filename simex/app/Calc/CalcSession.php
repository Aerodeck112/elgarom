<?php

namespace App\Calc;

use App\Calc\Engine\CalcException;
use App\Calc\Engine\ExcelError;
use App\Calc\Engine\FormulaParser;
use App\Calc\Engine\Workbook;
use App\Calc\Engine\XlsxModelReader;

/**
 * One calculation: a validated workbook model + global parameter overrides + the user's inputs.
 * Users can only write constants into cells the workbook marks as inputs, on sheets whose role allows it.
 */
final class CalcSession
{
    public readonly Workbook $wb;

    /** @var array<string, array> sheet name → model sheet */
    private array $sheets = [];

    private array $lists = [];

    public function __construct(public readonly ModelDefinition $def, private readonly array $model)
    {
        $this->wb = Workbook::fromModel($model);
        foreach ($model['sheets'] as $sheet) {
            $this->sheets[$sheet['name']] = $sheet;
        }
    }

    public function isInputCell(string $sheet, int $row, int $col, array $roles): bool
    {
        return in_array($this->def->role($sheet), $roles, true) && isset($this->sheets[$sheet]['unlocked'][$row][$col]);
    }

    /**
     * @param  array<string,mixed>  $values  "Sheet!A1" => scalar
     * @param  list<string>  $roles  sheet roles the caller may write to ('input', 'params')
     * @return list<string> rejected cell references
     */
    public function apply(array $values, array $roles): array
    {
        $rejected = [];
        foreach ($values as $ref => $value) {
            $pos = strrpos((string) $ref, '!');
            if ($pos === false) {
                $rejected[] = (string) $ref;

                continue;
            }
            $sheet = substr($ref, 0, $pos);
            $addr = substr($ref, $pos + 1);
            try {
                [$r, $c] = XlsxModelReader::addr($addr);
            } catch (CalcException) {
                $rejected[] = $ref;

                continue;
            }
            if (! isset($this->sheets[$sheet]) || ! $this->isInputCell($sheet, $r, $c, $roles)) {
                $rejected[] = $ref;

                continue;
            }
            $this->wb->set($sheet, $addr, self::normalise($value));
        }

        return $rejected;
    }

    /** Form value → cell constant, the way Excel interprets typed text. */
    public static function normalise(mixed $value): float|string|bool|null
    {
        if ($value === null || is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return is_finite((float) $value) ? (float) $value : null;
        }
        if (! is_string($value)) {
            return null;
        }
        $value = trim(mb_substr($value, 0, 2000));
        if ($value === '') {
            return null;
        }
        if (preg_match('/^-?\d{1,15}([.,]\d{1,12})?$/', $value)) {
            return (float) str_replace(',', '.', $value);
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return (float) (intdiv(gmmktime(0, 0, 0, (int) $m[2], (int) $m[3], (int) $m[1]), 86400) + 25569);
        }

        return $value;
    }

    private function options(string $sheet): array
    {
        if (isset($this->lists[$sheet])) {
            return $this->lists[$sheet];
        }
        $cells = [];
        $lists = [];
        foreach ($this->sheets[$sheet]['validations'] as $dv) {
            $isList = $dv['type'] === 'list' && $dv['formula1'] !== null;
            $isNumber = in_array($dv['type'], ['decimal', 'whole'], true);
            $isDate = $dv['type'] === 'date';
            if (! $isList && ! $isNumber && ! $isDate) {
                continue;
            }
            $index = null;
            if ($isList) {
                $opts = $this->wb->listOptions($sheet, $dv['formula1']);
                if ($opts === null || $opts === []) {
                    continue;
                }
                $lists[] = $opts;
                $index = count($lists) - 1;
            }
            foreach (preg_split('/\s+/', trim($dv['sqref'])) as $range) {
                [$a, $b] = array_pad(explode(':', $range), 2, null);
                [$r1, $c1] = XlsxModelReader::addr($a);
                [$r2, $c2] = $b ? XlsxModelReader::addr($b) : [$r1, $c1];
                if (($r2 - $r1 + 1) * ($c2 - $c1 + 1) > 20000) {
                    continue;
                }
                for ($r = $r1; $r <= $r2; $r++) {
                    for ($c = $c1; $c <= $c2; $c++) {
                        $cells[$r][$c] = $isList ? ['l', $index] : [$isDate ? 'd' : 'n', null];
                    }
                }
            }
        }

        return $this->lists[$sheet] = [$cells, $lists];
    }

    /**
     * A sheet as a grid for the browser.
     *
     * @param  list<string>  $writableRoles  roles the current user may edit
     */
    public function sheetView(string $sheet, array $writableRoles): array
    {
        $data = $this->sheets[$sheet] ?? throw new CalcException("Foaia «{$sheet}» nu există în model.");
        $sid = $this->wb->sheetId($sheet);
        [$dvCells, $lists] = $this->options($sheet);
        $editable = in_array($this->def->role($sheet), $writableRoles, true);
        $styles = $this->model['styles'] ?? [];
        $rows = [];
        $maxCol = 1;
        for ($r = 1; $r <= $data['maxRow']; $r++) {
            $row = [];
            for ($c = 1; $c <= $data['maxCol']; $c++) {
                $isInput = $editable && isset($data['unlocked'][$r][$c]);
                $v = $this->wb->cellValue($sid, $r, $c);
                if ($v === null && ! $isInput) {
                    continue;
                }
                $style = $styles[$data['st'][$r][$c] ?? 0] ?? null;
                $cell = ['c' => $c];
                if ($v !== null) {
                    $cell['d'] = Display::format($v, $style['fmt'] ?? null);
                    if (is_float($v)) {
                        $cell['n'] = 1;
                    }
                    if ($v instanceof ExcelError) {
                        $cell['e'] = 1;
                    }
                }
                if ($isInput) {
                    $cell['i'] = 1;
                    $cell['v'] = Display::raw($v);
                    $dv = $dvCells[$r][$c] ?? null;
                    if ($dv !== null) {
                        $cell['t'] = $dv[0];
                        if ($dv[0] === 'l') {
                            $cell['o'] = $dv[1];
                        }
                    }
                    $fmt = $style['fmt'] ?? null;
                    if ($fmt !== null && str_contains($fmt, '%')) {
                        $cell['p'] = 1;
                    }
                    if (($cell['t'] ?? null) === 'd' && is_float($v)) {
                        $cell['v'] = gmdate('Y-m-d', (int) round(($v - 25569) * 86400));
                    }
                } elseif (isset($data['cells'][$r][$c]) && is_array($data['cells'][$r][$c])) {
                    $cell['f'] = 1;
                }
                if (! empty($style['bold'])) {
                    $cell['b'] = 1;
                }
                $row[] = $cell;
                $maxCol = max($maxCol, $c);
            }
            if ($row !== []) {
                $rows[] = ['r' => $r, 'cells' => $row];
            }
        }
        $colNames = [];
        for ($c = 1; $c <= $maxCol; $c++) {
            $colNames[] = FormulaParser::numToCol($c);
        }

        return [
            'name' => $sheet,
            'label' => $this->def->sheets()[$sheet]['label'] ?? $sheet,
            'role' => $this->def->role($sheet),
            'editable' => $editable,
            'cols' => $colNames,
            'rows' => $rows,
            'lists' => $lists,
        ];
    }

    private function cell(string $ref): mixed
    {
        $pos = strrpos($ref, '!');

        return $this->wb->get(substr($ref, 0, $pos), substr($ref, $pos + 1));
    }

    private function fmtOf(string $ref): ?string
    {
        $pos = strrpos($ref, '!');
        [$r, $c] = XlsxModelReader::addr(substr($ref, $pos + 1));
        $sheet = $this->sheets[substr($ref, 0, $pos)] ?? null;

        return $this->model['styles'][$sheet['st'][$r][$c] ?? 0]['fmt'] ?? null;
    }

    /** Results of every variant: state, hours or total, detail lines and warnings. */
    public function summary(): array
    {
        $out = [];
        foreach ($this->def->variants() as $key => $variant) {
            $status = Display::text($this->cell($variant['status']));
            $state = 'block';
            if (preg_match($variant['ok'], $status)) {
                $state = 'ok';
            }
            if (isset($variant['warn']) && preg_match($variant['warn'], $status)) {
                $state = 'warn';
            }
            $warnings = [];
            foreach ($variant['warnings'] ?? [] as $ref) {
                $text = trim(Display::text($this->cell($ref)));
                if ($text !== '' && $text !== 'OK' && $text !== 'Fără avertismente') {
                    $warnings[] = $text;
                }
            }
            if ($state === 'ok' && $warnings !== [] && preg_grep('/^ATENȚIE/u', $warnings)) {
                $state = 'warn';
            }
            $outputs = [];
            foreach ($variant['outputs'] ?? [] as $label => $ref) {
                $v = $this->cell($ref);
                $outputs[] = ['label' => $label, 'ref' => $ref, 'value' => Display::raw($v), 'display' => Display::format($v, $this->fmtOf($ref))];
            }
            $hours = isset($variant['hours']) ? Display::numberOrNull($this->cell($variant['hours'])) : null;
            $total = isset($variant['total']) ? Display::numberOrNull($this->cell($variant['total'])) : null;
            if ($state === 'block') {
                // A blocked calculation never yields a usable figure.
                $hours = null;
                $total = null;
            }
            $out[$key] = [
                'key' => $key,
                'label' => $variant['label'],
                'state' => $state,
                'status' => isset($variant['status_text']) ? Display::text($this->cell($variant['status_text'])) : $status,
                'hours' => $hours,
                'total' => $total,
                'currency' => $variant['currency'] ?? null,
                'outputs' => $outputs,
                'warnings' => array_values(array_unique($warnings)),
            ];
        }

        return $out;
    }

    /** Offer lines of a cost model (one per priced part). */
    public function lines(): array
    {
        $spec = $this->def->lines;
        if (! $spec) {
            return [];
        }
        $out = [];
        for ($r = $spec['first']; $r <= $spec['last']; $r++) {
            $line = [];
            foreach ($spec['cols'] as $key => $col) {
                $line[$key] = Display::raw($this->wb->get($spec['sheet'], $col.$r));
            }
            if (($line['code'] ?? '') === '' || $line['code'] === null) {
                continue;
            }
            $out[] = $line;
        }

        return $out;
    }

    /** Pre-offer checks of a cost model. */
    public function checks(): array
    {
        $spec = $this->def->checks;
        if (! $spec) {
            return [];
        }
        $out = [];
        for ($r = $spec['first']; $r <= $spec['last']; $r++) {
            $label = Display::text($this->wb->get($spec['sheet'], $spec['label'].$r));
            if ($label === '') {
                continue;
            }
            $value = $this->wb->get($spec['sheet'], $spec['value'].$r);
            $expected = Display::text($this->wb->get($spec['sheet'], $spec['expected'].$r));
            $ok = match (true) {
                $expected === '0' => $value === 0.0,
                $expected === '> 0' => is_float($value) && $value > 0,
                default => true,
            };
            $out[] = ['label' => $label, 'value' => Display::format($value), 'expected' => $expected, 'where' => Display::text($this->wb->get($spec['sheet'], $spec['where'].$r)), 'ok' => $ok];
        }

        return $out;
    }

    /** Current values of all input cells that differ from the workbook – what gets stored with a calculation. */
    public function sheetNames(): array
    {
        return array_keys($this->sheets);
    }
}
