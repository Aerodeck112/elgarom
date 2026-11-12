<?php

namespace App\Calc\Engine;

/**
 * Deterministic evaluator for a workbook model produced by XlsxModelReader.
 *
 * Values: float (all numbers), string, bool, null (empty cell), ExcelError, RangeRef (reference),
 * or a PHP array of rows (array value). Cells are evaluated lazily and memoised; changing an input
 * with set() clears the memo. No formula is ever taken from user input: users only write constants
 * into input cells.
 */
final class Workbook
{
    use Functions;

    /** @var list<string> */
    public array $sheetNames = [];

    /** @var array<string,int> */
    private array $sheetIndex = [];

    private array $consts = [];

    private array $formulas = [];

    private array $ast = [];

    private array $memo = [];

    private array $arrMemo = [];

    private array $busy = [];

    private array $dims = [];

    private array $nameDefs = [];

    private array $nameAst = [];

    private FormulaParser $parser;

    /** Excel serial number returned by TODAY(). Fixed per calculation so results are reproducible. */
    public float $today;

    public int $evaluated = 0;

    // Evaluation context of the formula cell being computed.
    private int $cs = 0;

    private int $cr = 0;

    private int $cc = 0;

    private int $dr = 0;

    private int $dc = 0;

    private bool $arr = false;

    public static function fromModel(array $model): self
    {
        $wb = new self;
        foreach ($model['sheets'] as $i => $sheet) {
            $wb->sheetNames[$i] = $sheet['name'];
            $wb->sheetIndex[mb_strtoupper($sheet['name'])] = $i;
            $wb->dims[$i] = [$sheet['maxRow'], $sheet['maxCol']];
            $consts = [];
            $formulas = [];
            foreach ($sheet['cells'] as $r => $row) {
                foreach ($row as $c => $cell) {
                    if (is_array($cell)) {
                        if (isset($cell['err'])) {
                            $consts[$r][$c] = ExcelError::of($cell['err']);
                        } else {
                            $formulas[$r][$c] = $cell;
                        }
                    } else {
                        $consts[$r][$c] = $cell;
                    }
                }
            }
            $wb->consts[$i] = $consts;
            $wb->formulas[$i] = $formulas;
        }
        $wb->nameDefs = $model['names'] ?? [];
        $wb->parser = new FormulaParser($wb->sheetIndex);
        $wb->today = self::dateSerial(time());

        return $wb;
    }

    public static function dateSerial(int $timestamp): float
    {
        return (float) (intdiv(strtotime(date('Y-m-d', $timestamp).' 00:00:00 UTC'), 86400) + 25569);
    }

    public function hasSheet(string $name): bool
    {
        return isset($this->sheetIndex[mb_strtoupper($name)]);
    }

    public function sheetId(string $name): int
    {
        return $this->sheetIndex[mb_strtoupper($name)] ?? throw new CalcException("Foaia «{$name}» nu există în model.");
    }

    /** Writes a constant into a cell (replacing a formula if there is one) and invalidates results. */
    public function set(string $sheet, string $addr, mixed $value): void
    {
        $s = $this->sheetId($sheet);
        [$r, $c] = XlsxModelReader::addr($addr);
        if (is_int($value)) {
            $value = (float) $value;
        }
        if ($value !== null && ! is_float($value) && ! is_string($value) && ! is_bool($value)) {
            throw new CalcException('Valoare de intrare nepermisă pentru '.$sheet.'!'.$addr);
        }
        unset($this->formulas[$s][$r][$c]);
        if ($value === null || $value === '') {
            unset($this->consts[$s][$r][$c]);
        } else {
            $this->consts[$s][$r][$c] = $value;
        }
        $this->dims[$s] = [max($this->dims[$s][0], $r), max($this->dims[$s][1], $c)];
        $this->memo = [];
        $this->arrMemo = [];
    }

    /** Final value of a cell: float|string|bool|null|ExcelError. */
    public function get(string $sheet, string $addr): mixed
    {
        [$r, $c] = XlsxModelReader::addr($addr);

        return $this->cellValue($this->sheetId($sheet), $r, $c);
    }

    /** Options of a list validation ("a,b,c", a range or a defined name), evaluated on the given sheet. */
    public function listOptions(string $sheet, string $formula): ?array
    {
        $formula = trim($formula);
        if (strlen($formula) >= 2 && $formula[0] === '"' && str_ends_with($formula, '"')) {
            return array_values(array_filter(array_map('trim', explode(',', substr($formula, 1, -1))), fn ($x) => $x !== ''));
        }
        $saved = [$this->cs, $this->cr, $this->cc, $this->dr, $this->dc, $this->arr];
        try {
            [$this->cs, $this->cr, $this->cc, $this->dr, $this->dc, $this->arr] = [$this->sheetId($sheet), 1, 1, 0, 0, true];
            $v = $this->ev($this->parser->parse($formula));
            $out = [];
            foreach ($this->flat($v) as $x) {
                if ($x !== null && $x !== '' && ! $x instanceof ExcelError) {
                    $out[] = is_float($x) ? self::numToStr($x) : (string) $x;
                }
            }

            return $out;
        } catch (CalcException) {
            return null;
        } finally {
            [$this->cs, $this->cr, $this->cc, $this->dr, $this->dc, $this->arr] = $saved;
        }
    }

    /** Functions used by the workbook that this engine does not implement (empty = fully supported). */
    public function unsupportedFunctions(): array
    {
        $supported = array_flip(self::supportedFunctions());
        $missing = [];
        foreach ($this->formulas as $rows) {
            foreach ($rows as $cols) {
                foreach ($cols as $f) {
                    if (isset($f['f']) && preg_match_all('/(?<![A-Za-z0-9_.])(?:_xlfn\.|_xlws\.)?([A-Z][A-Z0-9.]*)\(/', $f['f'], $m)) {
                        foreach ($m[1] as $fn) {
                            if (! isset($supported[$fn])) {
                                $missing[$fn] = true;
                            }
                        }
                    }
                }
            }
        }

        return array_keys($missing);
    }

    public function isFormula(string $sheet, string $addr): bool
    {
        [$r, $c] = XlsxModelReader::addr($addr);

        return isset($this->formulas[$this->sheetId($sheet)][$r][$c]);
    }

    /** Formula text of a cell as written in the workbook (shared formulas show their master). */
    public function formulaText(string $sheet, string $addr): ?string
    {
        $s = $this->sheetId($sheet);
        [$r, $c] = XlsxModelReader::addr($addr);
        $f = $this->formulas[$s][$r][$c] ?? null;
        if ($f === null) {
            return null;
        }
        if (isset($f['sf']) || isset($f['af'])) {
            [$mr, $mc] = $f['sf'] ?? $f['af'];

            return $this->formulas[$s][$mr][$mc]['f'] ?? null;
        }

        return $f['f'];
    }

    /**
     * Direct precedents of a formula cell with their current values – used by the "how was this
     * computed" view. Ranges larger than $maxCells are reported as a range without values.
     *
     * @return list<array{ref:string,value:mixed}>
     */
    public function precedents(string $sheet, string $addr, int $maxCells = 12): array
    {
        $s = $this->sheetId($sheet);
        [$r, $c] = XlsxModelReader::addr($addr);
        $f = $this->formulas[$s][$r][$c] ?? null;
        if ($f === null || isset($f['af'])) {
            return [];
        }
        [$mr, $mc] = $f['sf'] ?? [$r, $c];
        $refs = [];
        $this->collectRefs($this->astOf($s, $mr, $mc), $s, $r - $mr, $c - $mc, $refs);
        $out = [];
        foreach ($refs as [$rs, $r1, $c1, $r2, $c2]) {
            $label = ($rs === $s ? '' : $this->sheetNames[$rs].'!').FormulaParser::numToCol($c1).$r1;
            if ($r1 === $r2 && $c1 === $c2) {
                $out[$label] = ['ref' => $label, 'value' => $this->cellValue($rs, $r1, $c1)];
            } else {
                $label .= ':'.FormulaParser::numToCol($c2).$r2;
                $out[$label] = ['ref' => $label, 'value' => null];
            }
            if (count($out) >= 40) {
                break;
            }
        }

        return array_values($out);
    }

    private function collectRefs(array $n, int $s, int $dr, int $dc, array &$refs): void
    {
        switch ($n[0]) {
            case 'c':
                $rs = $n[1] === -1 ? $s : $n[1];
                $row = $n[4] ? $n[2] : $n[2] + $dr;
                $col = $n[5] ? $n[3] : $n[3] + $dc;
                $refs[] = [$rs, $row, $col, $row, $col];
                break;
            case 'r':
                $refs[] = [$n[1] === -1 ? $s : $n[1], $n[6] ? $n[2] : $n[2] + $dr, $n[7] ? $n[3] : $n[3] + $dc, $n[8] ? $n[4] : $n[4] + $dr, $n[9] ? $n[5] : $n[5] + $dc];
                break;
            case 'nm':
                $v = $this->evName($n[1]);
                if ($v instanceof RangeRef) {
                    $refs[] = [$v->sheet, $v->r1, $v->c1, $v->r2, $v->c2];
                }
                break;
            case 'op':
            case 'rng':
                $this->collectRefs($n[count($n) - 2], $s, $dr, $dc, $refs);
                $this->collectRefs($n[count($n) - 1], $s, $dr, $dc, $refs);
                break;
            case 'u':
                $this->collectRefs($n[2], $s, $dr, $dc, $refs);
                break;
            case 'pct':
                $this->collectRefs($n[1], $s, $dr, $dc, $refs);
                break;
            case 'f':
                foreach ($n[2] as $a) {
                    $this->collectRefs($a, $s, $dr, $dc, $refs);
                }
                break;
        }
    }

    /** Every formula cell as [sheetName, 'A1', cachedValue] – used to validate the engine against Excel. */
    public function formulaCells(): iterable
    {
        foreach ($this->formulas as $s => $rows) {
            foreach ($rows as $r => $cols) {
                foreach ($cols as $c => $f) {
                    yield [$this->sheetNames[$s], FormulaParser::numToCol($c).$r, $f['v'] ?? null];
                }
            }
        }
    }

    public function cellValue(int $s, int $r, int $c): mixed
    {
        if (isset($this->memo[$s][$r][$c])) {
            return $this->memo[$s][$r][$c];
        }
        $f = $this->formulas[$s][$r][$c] ?? null;
        if ($f === null) {
            return $this->consts[$s][$r][$c] ?? null;
        }
        if (isset($this->busy[$s][$r][$c])) {
            throw new CalcException('Referință circulară la '.$this->sheetNames[$s].'!'.FormulaParser::numToCol($c).$r);
        }
        $this->busy[$s][$r][$c] = true;
        $saved = [$this->cs, $this->cr, $this->cc, $this->dr, $this->dc, $this->arr];
        try {
            if (isset($f['af'])) {
                [$mr, $mc] = $f['af'];
                $this->cellValue($s, $mr, $mc);
                $raw = $this->arrMemo[$s][$mr][$mc] ?? null;
                $v = is_array($raw) ? ($raw[$r - $mr][$c - $mc] ?? ExcelError::na()) : $raw;
                $v ??= 0.0;
            } else {
                if (isset($f['sf'])) {
                    [$mr, $mc] = $f['sf'];
                    $master = $this->formulas[$s][$mr][$mc] ?? null;
                    if ($master === null || ! isset($master['f'])) {
                        throw new CalcException('Formula partajată și-a pierdut sursa la '.$this->sheetNames[$s].'!'.FormulaParser::numToCol($c).$r);
                    }
                    $ast = $this->astOf($s, $mr, $mc);
                    $this->dr = $r - $mr;
                    $this->dc = $c - $mc;
                    $this->arr = ! empty($master['a']);
                } else {
                    $ast = $this->astOf($s, $r, $c);
                    $this->dr = 0;
                    $this->dc = 0;
                    $this->arr = ! empty($f['a']);
                }
                $this->cs = $s;
                $this->cr = $r;
                $this->cc = $c;
                $raw = $this->ev($ast);
                if (isset($f['ref'])) {
                    $this->arrMemo[$s][$r][$c] = $raw instanceof RangeRef ? $this->toArray($raw) : $raw;
                }
                $v = $this->finalize($raw);
            }
        } finally {
            [$this->cs, $this->cr, $this->cc, $this->dr, $this->dc, $this->arr] = $saved;
            unset($this->busy[$s][$r][$c]);
        }
        $this->evaluated++;

        return $this->memo[$s][$r][$c] = $v;
    }

    private function astOf(int $s, int $r, int $c): array
    {
        return $this->ast[$s][$r][$c] ??= $this->parser->parse($this->formulas[$s][$r][$c]['f']);
    }

    private function finalize(mixed $v): mixed
    {
        while (true) {
            if (is_array($v)) {
                $v = $v[0][0] ?? null;
            } elseif ($v instanceof RangeRef) {
                $v = $this->deref($v);
                if (is_array($v)) {
                    continue;
                }
            }
            break;
        }
        if ($v === null) {
            return 0.0;
        }

        return is_int($v) ? (float) $v : $v;
    }

    /** Reference → value: single cell, array (array mode) or implicit intersection. */
    private function deref(RangeRef $ref): mixed
    {
        if ($ref->r1 === $ref->r2 && $ref->c1 === $ref->c2) {
            return $this->cellValue($ref->sheet, $ref->r1, $ref->c1);
        }
        if ($this->arr) {
            return $this->toArray($ref);
        }
        if ($ref->c1 === $ref->c2 && $this->cr >= $ref->r1 && $this->cr <= $ref->r2) {
            return $this->cellValue($ref->sheet, $this->cr, $ref->c1);
        }
        if ($ref->r1 === $ref->r2 && $this->cc >= $ref->c1 && $this->cc <= $ref->c2) {
            return $this->cellValue($ref->sheet, $ref->r1, $this->cc);
        }

        return ExcelError::value();
    }

    private function clamp(RangeRef $ref): array
    {
        [$maxR, $maxC] = $this->dims[$ref->sheet];

        return [$ref->r1, $ref->c1, min($ref->r2, max($maxR, $ref->r1)), min($ref->c2, max($maxC, $ref->c1))];
    }

    private function toArray(RangeRef $ref): array
    {
        [$r1, $c1, $r2, $c2] = $this->clamp($ref);
        $out = [];
        for ($r = $r1; $r <= $r2; $r++) {
            $row = [];
            for ($c = $c1; $c <= $c2; $c++) {
                $row[] = $this->cellValue($ref->sheet, $r, $c);
            }
            $out[] = $row;
        }

        return $out;
    }

    /** Row-major flat list of a reference / array / scalar. */
    private function flat(mixed $v): array
    {
        if ($v instanceof RangeRef) {
            [$r1, $c1, $r2, $c2] = $this->clamp($v);
            $out = [];
            for ($r = $r1; $r <= $r2; $r++) {
                for ($c = $c1; $c <= $c2; $c++) {
                    $out[] = $this->cellValue($v->sheet, $r, $c);
                }
            }

            return $out;
        }
        if (is_array($v)) {
            return $v === [] ? [] : array_merge(...$v);
        }

        return [$v];
    }

    private function ev(array $n): mixed
    {
        switch ($n[0]) {
            case 'n':
            case 's':
            case 'b':
                return $n[1];
            case 'c':
                $row = $n[4] ? $n[2] : $n[2] + $this->dr;
                $col = $n[5] ? $n[3] : $n[3] + $this->dc;
                if ($row < 1 || $col < 1) {
                    return ExcelError::ref();
                }

                return new RangeRef($n[1] === -1 ? $this->cs : $n[1], $row, $col, $row, $col);
            case 'r':
                $r1 = $n[6] ? $n[2] : $n[2] + $this->dr;
                $c1 = $n[7] ? $n[3] : $n[3] + $this->dc;
                $r2 = $n[8] ? $n[4] : $n[4] + $this->dr;
                $c2 = $n[9] ? $n[5] : $n[5] + $this->dc;
                if (min($r1, $r2, $c1, $c2) < 1) {
                    return ExcelError::ref();
                }

                return new RangeRef($n[1] === -1 ? $this->cs : $n[1], min($r1, $r2), min($c1, $c2), max($r1, $r2), max($c1, $c2));
            case 'op':
                $l = $this->evVal($n[2]);
                $r = $this->evVal($n[3]);
                if (is_array($l) || is_array($r)) {
                    $op = $n[1];

                    return $this->broadcast(fn ($a, $b) => $this->binary($op, $a, $b), $l, $r);
                }

                return $this->binary($n[1], $l, $r);
            case 'f':
                return $this->call($n[1], $n[2]);
            case 'u':
                $v = $this->evVal($n[2]);
                if ($n[1] === '+') {
                    return $v;
                }
                if (is_array($v)) {
                    return $this->map1(fn ($x) => $this->binary('-', 0.0, $x), $v);
                }

                return $this->binary('-', 0.0, $v);
            case 'pct':
                $v = $this->evVal($n[1]);
                if (is_array($v)) {
                    return $this->map1(fn ($x) => $this->binary('/', $x, 100.0), $v);
                }

                return $this->binary('/', $v, 100.0);
            case 'nm':
                return $this->evName($n[1]);
            case 'e':
                return ExcelError::of($n[1]);
            case 'm':
                return null;
            case 'rng':
                $a = $this->ev($n[1]);
                $b = $this->ev($n[2]);
                if ($a instanceof ExcelError) {
                    return $a;
                }
                if ($b instanceof ExcelError) {
                    return $b;
                }
                if (! $a instanceof RangeRef || ! $b instanceof RangeRef || $a->sheet !== $b->sheet) {
                    return ExcelError::ref();
                }

                return new RangeRef($a->sheet, min($a->r1, $b->r1), min($a->c1, $b->c1), max($a->r2, $b->r2), max($a->c2, $b->c2));
            case 'a':
                return $n[1];
        }
        throw new CalcException('Nod de formulă necunoscut.');
    }

    /** Like ev() but never returns a reference. */
    private function evVal(array $n): mixed
    {
        if ($n[0] === 'c') {
            $row = $n[4] ? $n[2] : $n[2] + $this->dr;
            $col = $n[5] ? $n[3] : $n[3] + $this->dc;
            if ($row < 1 || $col < 1) {
                return ExcelError::ref();
            }

            return $this->cellValue($n[1] === -1 ? $this->cs : $n[1], $row, $col);
        }
        if ($n[0] === 'n' || $n[0] === 's' || $n[0] === 'b') {
            return $n[1];
        }
        $v = $this->ev($n);

        return $v instanceof RangeRef ? $this->deref($v) : $v;
    }

    /** Evaluates an argument in array mode (ranges stay references, expressions become arrays). */
    private function evArr(array $n): mixed
    {
        $old = $this->arr;
        $this->arr = true;
        try {
            return $this->ev($n);
        } finally {
            $this->arr = $old;
        }
    }

    private function evName(string $name): mixed
    {
        if (! isset($this->nameDefs[$name])) {
            return ExcelError::name();
        }
        $ast = $this->nameAst[$name] ??= $this->parser->parse($this->nameDefs[$name]);
        $saved = [$this->dr, $this->dc];
        $this->dr = 0;
        $this->dc = 0;
        try {
            return $this->ev($ast);
        } finally {
            [$this->dr, $this->dc] = $saved;
        }
    }

    private function binary(string $op, mixed $l, mixed $r): mixed
    {
        if ($l instanceof ExcelError) {
            return $l;
        }
        if ($r instanceof ExcelError) {
            return $r;
        }
        switch ($op) {
            case '+':
            case '-':
            case '*':
            case '/':
            case '^':
                if (! is_float($l)) {
                    $l = $this->num($l);
                    if ($l instanceof ExcelError) {
                        return $l;
                    }
                }
                if (! is_float($r)) {
                    $r = $this->num($r);
                    if ($r instanceof ExcelError) {
                        return $r;
                    }
                }
                switch ($op) {
                    case '+':
                        return $l + $r;
                    case '-':
                        return $l - $r;
                    case '*':
                        return $l * $r;
                    case '/':
                        return $r == 0.0 ? ExcelError::div0() : $l / $r;
                    default:
                        if ($l == 0.0 && $r == 0.0) {
                            return ExcelError::num();
                        }
                        if ($l == 0.0 && $r < 0) {
                            return ExcelError::div0();
                        }
                        $p = @pow($l, $r);

                        return is_nan($p) || is_infinite($p) ? ExcelError::num() : (float) $p;
                }
            case '&':
                return $this->str($l).$this->str($r);
            case '=':
                return $this->cmp($l, $r) === 0;
            case '<>':
                return $this->cmp($l, $r) !== 0;
            case '<':
                return $this->cmp($l, $r) < 0;
            case '>':
                return $this->cmp($l, $r) > 0;
            case '<=':
                return $this->cmp($l, $r) <= 0;
            case '>=':
                return $this->cmp($l, $r) >= 0;
        }
        throw new CalcException("Operator necunoscut: {$op}");
    }

    /** Excel ordering: numbers < text < booleans; text compares case-insensitively; empty adapts to the other side. */
    private function cmp(mixed $a, mixed $b): int
    {
        if ($a === null) {
            $a = is_string($b) ? '' : (is_bool($b) ? false : 0.0);
        }
        if ($b === null) {
            $b = is_string($a) ? '' : (is_bool($a) ? false : 0.0);
        }
        $ta = is_float($a) ? 1 : (is_string($a) ? 2 : 3);
        $tb = is_float($b) ? 1 : (is_string($b) ? 2 : 3);
        if ($ta !== $tb) {
            return $ta <=> $tb;
        }
        if ($ta === 1) {
            if ($a == $b) {
                return 0;
            }
            // Excel compares numbers at 15 significant digits.
            if (abs($a - $b) <= 1e-14 * max(abs($a), abs($b)) && sprintf('%.14e', $a) === sprintf('%.14e', $b)) {
                return 0;
            }

            return $a <=> $b;
        }
        if ($ta === 2) {
            return $a === $b ? 0 : (mb_strtolower($a) <=> mb_strtolower($b));
        }

        return $a <=> $b;
    }

    /** Scalar → number or error (arithmetic coercion). */
    private function num(mixed $v): float|ExcelError
    {
        if (is_float($v)) {
            return $v;
        }
        if ($v === null) {
            return 0.0;
        }
        if (is_bool($v)) {
            return $v ? 1.0 : 0.0;
        }
        if (is_int($v)) {
            return (float) $v;
        }
        if ($v instanceof ExcelError) {
            return $v;
        }
        if (is_string($v)) {
            $p = self::parseNumber($v);

            return $p ?? ExcelError::value();
        }

        return ExcelError::value();
    }

    public static function parseNumber(string $s): ?float
    {
        $s = trim($s);
        if ($s === '') {
            return null;
        }
        if (preg_match('/^[+-]?(\d+\.?\d*|\.\d+)([eE][+-]?\d+)?$/', $s)) {
            return (float) $s;
        }
        if (preg_match('/^([+-]?(?:\d+\.?\d*|\.\d+))\s*%$/', $s, $m)) {
            return (float) $m[1] / 100;
        }
        if (preg_match('/^[+-]?\d{1,3}(,\d{3})+(\.\d*)?$/', $s)) {
            return (float) str_replace(',', '', $s);
        }

        return null;
    }

    public function str(mixed $v): string
    {
        if (is_string($v)) {
            return $v;
        }
        if ($v === null) {
            return '';
        }
        if (is_bool($v)) {
            return $v ? 'TRUE' : 'FALSE';
        }
        if ($v instanceof ExcelError) {
            return $v->code;
        }

        return self::numToStr((float) $v);
    }

    /** Number → text as Excel's General format does when concatenating (15 significant digits). */
    public static function numToStr(float $v): string
    {
        if ($v == floor($v) && abs($v) < 1e15) {
            return sprintf('%.0f', $v == 0.0 ? 0.0 : $v);
        }
        $s = sprintf('%.15G', $v);
        if (str_contains($s, 'E')) {
            if (abs($v) >= 1e-9 && abs($v) < 1) {
                return rtrim(rtrim(sprintf('%.20F', (float) $s), '0'), '.');
            }
            [$mant, $exp] = explode('E', $s);

            return $mant.'E'.($exp[0] === '-' ? '-' : '+').str_pad(ltrim($exp, '+-'), 2, '0', STR_PAD_LEFT);
        }

        return $s;
    }

    private function map1(callable $fn, array $a): array
    {
        $out = [];
        foreach ($a as $row) {
            $o = [];
            foreach ($row as $x) {
                $o[] = $fn($x);
            }
            $out[] = $o;
        }

        return $out;
    }

    private function broadcast(callable $fn, mixed $a, mixed $b): array
    {
        $ar = is_array($a) ? count($a) : 1;
        $ac = is_array($a) ? count($a[0] ?? []) : 1;
        $br = is_array($b) ? count($b) : 1;
        $bc = is_array($b) ? count($b[0] ?? []) : 1;
        $rows = max($ar, $br);
        $cols = max($ac, $bc);
        $out = [];
        for ($i = 0; $i < $rows; $i++) {
            $row = [];
            for ($j = 0; $j < $cols; $j++) {
                $x = is_array($a) ? ($a[$ar === 1 ? 0 : $i][$ac === 1 ? 0 : $j] ?? ($i < $ar && $j < $ac ? null : ExcelError::na())) : $a;
                $y = is_array($b) ? ($b[$br === 1 ? 0 : $i][$bc === 1 ? 0 : $j] ?? ($i < $br && $j < $bc ? null : ExcelError::na())) : $b;
                $row[] = $fn($x, $y);
            }
            $out[] = $row;
        }

        return $out;
    }
}
