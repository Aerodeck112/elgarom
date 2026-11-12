<?php

namespace App\Calc\Engine;

/**
 * Excel worksheet functions used by the costing workbooks. Arguments arrive as AST nodes so that
 * IF / IFERROR stay lazy and reference-taking functions (INDEX, ROW, ISFORMULA…) see references.
 */
trait Functions
{
    /** Scalar functions: lifted element-wise over arrays in array mode; errors in arguments propagate. */
    private const SCALAR = [
        'ABS' => 1, 'COS' => 1, 'SIN' => 1, 'TAN' => 1, 'RADIANS' => 1, 'DEGREES' => 1, 'SQRT' => 1, 'EXP' => 1, 'LN' => 1,
        'ROUND' => 1, 'ROUNDUP' => 1, 'ROUNDDOWN' => 1, 'INT' => 1, 'MOD' => 1, 'POWER' => 1,
        'LEFT' => 1, 'RIGHT' => 1, 'MID' => 1, 'LEN' => 1, 'FIND' => 1, 'SEARCH' => 1, 'SUBSTITUTE' => 1, 'TRIM' => 1,
        'UPPER' => 1, 'LOWER' => 1, 'VALUE' => 1, 'TEXT' => 1, 'FIXED' => 1, 'NOT' => 1,
    ];

    /** Scalar functions that inspect errors instead of propagating them. */
    private const SCALAR_RAW = ['ISNUMBER' => 1, 'ISERROR' => 1, 'ISERR' => 1, 'ISNA' => 1, 'ISTEXT' => 1, 'ISBLANK' => 1, 'ISLOGICAL' => 1];

    public static function supportedFunctions(): array
    {
        return array_merge(array_keys(self::SCALAR), array_keys(self::SCALAR_RAW), [
            'IF', 'IFERROR', 'IFNA', 'AND', 'OR', 'TRUE', 'FALSE', 'PI', 'TODAY', 'N', 'SUM', 'COUNT', 'COUNTA', 'AVERAGE', 'MAX', 'MIN',
            'MEDIAN', 'STDEV', 'LARGE', 'SMALL', 'SUMPRODUCT', 'SUMIF', 'SUMIFS', 'COUNTIF', 'COUNTIFS', 'AVERAGEIFS', 'MAXIFS', 'MINIFS',
            'INDEX', 'MATCH', 'VLOOKUP', 'LOOKUP', 'ROW', 'ROWS', 'COLUMN', 'COLUMNS', 'ISFORMULA', 'AGGREGATE', 'TEXTJOIN', 'CHOOSE',
        ]);
    }

    private function call(string $name, array $args): mixed
    {
        if (isset(self::SCALAR[$name])) {
            return $this->lift($name, $args, true);
        }
        if (isset(self::SCALAR_RAW[$name])) {
            return $this->lift($name, $args, false);
        }
        switch ($name) {
            case 'IF':
                return $this->fnIf($args);
            case 'IFERROR':
            case 'IFNA':
                $v = $this->evVal($args[0]);
                $isErr = $name === 'IFNA'
                    ? fn ($x) => $x instanceof ExcelError && $x->code === '#N/A'
                    : fn ($x) => $x instanceof ExcelError;
                if (is_array($v)) {
                    $fb = isset($args[1]) ? $this->evVal($args[1]) : '';

                    return $this->broadcast(fn ($x, $y) => $isErr($x) ? ($y ?? 0.0) : $x, $v, $fb);
                }
                if (! $isErr($v)) {
                    return $v;
                }

                return isset($args[1]) ? $this->ev($args[1]) : '';
            case 'AND':
            case 'OR':
                return $this->fnAndOr($name === 'AND', $args);
            case 'TRUE':
                return true;
            case 'FALSE':
                return false;
            case 'PI':
                return M_PI;
            case 'TODAY':
                return $this->today;
            case 'N':
                $v = $this->ev($args[0]);
                if ($v instanceof RangeRef) {
                    $v = $this->cellValue($v->sheet, $v->r1, $v->c1);
                } elseif (is_array($v)) {
                    $v = $v[0][0] ?? null;
                }
                if ($v instanceof ExcelError || is_float($v)) {
                    return $v;
                }

                return is_bool($v) ? ($v ? 1.0 : 0.0) : 0.0;
            case 'SUM':
                $nums = $this->numbers($args);

                return $nums instanceof ExcelError ? $nums : (float) array_sum($nums);
            case 'COUNT':
                return $this->fnCount($args);
            case 'COUNTA':
                $n = 0;
                foreach ($args as $a) {
                    foreach ($this->flat($this->ev($a)) as $x) {
                        if ($x !== null) {
                            $n++;
                        }
                    }
                }

                return (float) $n;
            case 'AVERAGE':
                $nums = $this->numbers($args);
                if ($nums instanceof ExcelError) {
                    return $nums;
                }

                return $nums === [] ? ExcelError::div0() : array_sum($nums) / count($nums);
            case 'MAX':
            case 'MIN':
                $nums = $this->numbers($args);
                if ($nums instanceof ExcelError) {
                    return $nums;
                }
                if ($nums === []) {
                    return 0.0;
                }

                return (float) ($name === 'MAX' ? max($nums) : min($nums));
            case 'MEDIAN':
                $nums = $this->numbers($args);
                if ($nums instanceof ExcelError) {
                    return $nums;
                }

                return $nums === [] ? ExcelError::num() : self::median($nums);
            case 'STDEV':
            case 'STDEV.S':
                $nums = $this->numbers($args);
                if ($nums instanceof ExcelError) {
                    return $nums;
                }
                $n = count($nums);
                if ($n < 2) {
                    return ExcelError::div0();
                }
                $mean = array_sum($nums) / $n;
                $ss = 0.0;
                foreach ($nums as $x) {
                    $ss += ($x - $mean) ** 2;
                }

                return sqrt($ss / ($n - 1));
            case 'LARGE':
            case 'SMALL':
                $nums = $this->numbers([$args[0]]);
                if ($nums instanceof ExcelError) {
                    return $nums;
                }
                $k = $this->num($this->scalar($this->evVal($args[1])));
                if ($k instanceof ExcelError) {
                    return $k;
                }
                $k = (int) ceil($k);
                if ($k < 1 || $k > count($nums)) {
                    return ExcelError::num();
                }
                $name === 'LARGE' ? rsort($nums) : sort($nums);

                return $nums[$k - 1];
            case 'SUMPRODUCT':
                return $this->fnSumproduct($args);
            case 'COUNTIF':
                return $this->fnIfs('COUNT', null, [$args[0], $args[1]]);
            case 'COUNTIFS':
                return $this->fnIfs('COUNT', null, $args);
            case 'SUMIF':
                return $this->fnIfs('SUM', $args[2] ?? $args[0], [$args[0], $args[1]], isset($args[2]));
            case 'SUMIFS':
                return $this->fnIfs('SUM', $args[0], array_slice($args, 1));
            case 'AVERAGEIFS':
                return $this->fnIfs('AVERAGE', $args[0], array_slice($args, 1));
            case 'MAXIFS':
                return $this->fnIfs('MAX', $args[0], array_slice($args, 1));
            case 'MINIFS':
                return $this->fnIfs('MIN', $args[0], array_slice($args, 1));
            case 'INDEX':
                return $this->fnIndex($args);
            case 'MATCH':
                return $this->fnMatch($args);
            case 'VLOOKUP':
                return $this->fnVlookup($args);
            case 'LOOKUP':
                return $this->fnLookup($args);
            case 'ROW':
            case 'COLUMN':
                if (! isset($args[0])) {
                    return (float) ($name === 'ROW' ? $this->cr : $this->cc);
                }
                $ref = $this->ev($args[0]);
                if (! $ref instanceof RangeRef) {
                    return $ref instanceof ExcelError ? $ref : ExcelError::value();
                }
                if ($this->arr && ! $ref->isCell()) {
                    $out = [];
                    if ($name === 'ROW') {
                        for ($r = $ref->r1; $r <= $ref->r2; $r++) {
                            $out[] = [(float) $r];
                        }
                    } else {
                        $row = [];
                        for ($c = $ref->c1; $c <= $ref->c2; $c++) {
                            $row[] = (float) $c;
                        }
                        $out[] = $row;
                    }

                    return $out;
                }

                return (float) ($name === 'ROW' ? $ref->r1 : $ref->c1);
            case 'ROWS':
            case 'COLUMNS':
                $ref = $this->ev($args[0]);
                if ($ref instanceof RangeRef) {
                    return (float) ($name === 'ROWS' ? $ref->rows() : $ref->cols());
                }
                if (is_array($ref)) {
                    return (float) ($name === 'ROWS' ? count($ref) : count($ref[0] ?? []));
                }

                return $ref instanceof ExcelError ? $ref : 1.0;
            case 'ISFORMULA':
                $ref = $this->ev($args[0]);
                if (! $ref instanceof RangeRef) {
                    return $ref instanceof ExcelError ? $ref : ExcelError::value();
                }

                return isset($this->formulas[$ref->sheet][$ref->r1][$ref->c1]);
            case 'AGGREGATE':
                return $this->fnAggregate($args);
            case 'TEXTJOIN':
                $delim = $this->str($this->scalar($this->evVal($args[0])));
                $ignore = $this->truthy($this->scalar($this->evVal($args[1])));
                $parts = [];
                foreach (array_slice($args, 2) as $a) {
                    foreach ($this->flat($this->evVal($a)) as $x) {
                        if ($x instanceof ExcelError) {
                            return $x;
                        }
                        $t = $this->str($x);
                        if ($t !== '' || ! $ignore) {
                            $parts[] = $t;
                        }
                    }
                }

                return implode($delim, $parts);
            case 'CHOOSE':
                $i = $this->num($this->scalar($this->evVal($args[0])));
                if ($i instanceof ExcelError) {
                    return $i;
                }
                $i = (int) $i;

                return isset($args[$i]) && $i >= 1 ? $this->ev($args[$i]) : ExcelError::value();
        }
        throw new CalcException("Funcția {$name}() nu este suportată de motorul de calcul.");
    }

    /** Collapses an array/reference to one value where a function needs a single scalar. */
    private function scalar(mixed $v): mixed
    {
        if ($v instanceof RangeRef) {
            $v = $this->deref($v);
        }
        while (is_array($v)) {
            $v = $v[0][0] ?? null;
        }

        return $v;
    }

    private function truthy(mixed $v): bool
    {
        if (is_bool($v)) {
            return $v;
        }
        if (is_float($v)) {
            return $v != 0.0;
        }

        return is_string($v) && strtoupper($v) === 'TRUE';
    }

    private function lift(string $name, array $args, bool $propagate): mixed
    {
        $vals = [];
        $shape = null;
        foreach ($args as $a) {
            $v = $this->evVal($a);
            if (is_array($v)) {
                $shape = [max($shape[0] ?? 1, count($v)), max($shape[1] ?? 1, count($v[0] ?? []))];
            }
            $vals[] = $v;
        }
        if ($shape === null) {
            return $this->scalarFn($name, $vals, $propagate);
        }
        $out = [];
        for ($i = 0; $i < $shape[0]; $i++) {
            $row = [];
            for ($j = 0; $j < $shape[1]; $j++) {
                $cur = [];
                foreach ($vals as $v) {
                    if (is_array($v)) {
                        $rr = count($v) === 1 ? 0 : $i;
                        $cc = count($v[0] ?? []) === 1 ? 0 : $j;
                        $cur[] = array_key_exists($rr, $v) && array_key_exists($cc, $v[$rr]) ? $v[$rr][$cc] : ExcelError::na();
                    } else {
                        $cur[] = $v;
                    }
                }
                $row[] = $this->scalarFn($name, $cur, $propagate);
            }
            $out[] = $row;
        }

        return $out;
    }

    private function scalarFn(string $name, array $a, bool $propagate): mixed
    {
        if ($propagate) {
            foreach ($a as $v) {
                if ($v instanceof ExcelError) {
                    return $v;
                }
            }
        }
        $x = $a[0] ?? null;
        switch ($name) {
            case 'ISNUMBER':
                return is_float($x);
            case 'ISERROR':
                return $x instanceof ExcelError;
            case 'ISERR':
                return $x instanceof ExcelError && $x->code !== '#N/A';
            case 'ISNA':
                return $x instanceof ExcelError && $x->code === '#N/A';
            case 'ISTEXT':
                return is_string($x);
            case 'ISBLANK':
                return $x === null;
            case 'ISLOGICAL':
                return is_bool($x);
            case 'NOT':
                if (is_string($x)) {
                    return ExcelError::value();
                }

                return ! $this->truthy($x);
            case 'LEN':
                return (float) mb_strlen($this->str($x));
            case 'TRIM':
                return trim(preg_replace('/ {2,}/', ' ', $this->str($x)), ' ');
            case 'UPPER':
                return mb_strtoupper($this->str($x));
            case 'LOWER':
                return mb_strtolower($this->str($x));
            case 'LEFT':
            case 'RIGHT':
                $n = array_key_exists(1, $a) && $a[1] !== null ? $this->num($a[1]) : 1.0;
                if ($n instanceof ExcelError || $n < 0) {
                    return ExcelError::value();
                }
                $s = $this->str($x);

                return $name === 'LEFT' ? mb_substr($s, 0, (int) $n) : ((int) $n === 0 ? '' : mb_substr($s, -(int) $n));
            case 'MID':
                $start = $this->num($a[1] ?? null);
                $len = $this->num($a[2] ?? null);
                if ($start instanceof ExcelError || $len instanceof ExcelError || $start < 1 || $len < 0) {
                    return ExcelError::value();
                }

                return mb_substr($this->str($x), (int) $start - 1, (int) $len);
            case 'FIND':
            case 'SEARCH':
                $needle = $this->str($x);
                $hay = $this->str($a[1] ?? null);
                $start = array_key_exists(2, $a) && $a[2] !== null ? $this->num($a[2]) : 1.0;
                if ($start instanceof ExcelError || $start < 1 || $start > mb_strlen($hay) + 1) {
                    return ExcelError::value();
                }
                if ($name === 'SEARCH') {
                    if (strpbrk($needle, '*?~') !== false) {
                        $re = '/'.Criteria::wildcardRegex($needle).'/iu';
                        if (! preg_match($re, mb_substr($hay, (int) $start - 1), $m, PREG_OFFSET_CAPTURE)) {
                            return ExcelError::value();
                        }
                        $bytes = $m[0][1];

                        return (float) (mb_strlen(substr(mb_substr($hay, (int) $start - 1), 0, $bytes)) + (int) $start);
                    }
                    $pos = mb_stripos($hay, $needle, (int) $start - 1);
                } else {
                    $pos = $needle === '' ? (int) $start - 1 : mb_strpos($hay, $needle, (int) $start - 1);
                }

                return $pos === false ? ExcelError::value() : (float) ($pos + 1);
            case 'SUBSTITUTE':
                $s = $this->str($x);
                $old = $this->str($a[1] ?? null);
                $new = $this->str($a[2] ?? null);
                if ($old === '') {
                    return $s;
                }
                if (! array_key_exists(3, $a) || $a[3] === null) {
                    return str_replace($old, $new, $s);
                }
                $inst = $this->num($a[3]);
                if ($inst instanceof ExcelError || $inst < 1) {
                    return ExcelError::value();
                }
                $offset = 0;
                for ($i = 1; ; $i++) {
                    $p = strpos($s, $old, $offset);
                    if ($p === false) {
                        return $s;
                    }
                    if ($i === (int) $inst) {
                        return substr($s, 0, $p).$new.substr($s, $p + strlen($old));
                    }
                    $offset = $p + strlen($old);
                }
                // no break
            case 'VALUE':
                if (is_float($x)) {
                    return $x;
                }
                if (is_string($x)) {
                    return self::parseNumber($x) ?? ExcelError::value();
                }

                return $x === null ? 0.0 : ExcelError::value();
            case 'TEXT':
                $fmt = $this->str($a[1] ?? null);
                if (is_string($x)) {
                    $p = self::parseNumber($x);
                    if ($p === null) {
                        return $x;
                    }
                    $x = $p;
                }
                $v = $this->num($x);

                return $v instanceof ExcelError ? $v : self::formatNumber($v, $fmt);
            case 'FIXED':
                $v = $this->num($x);
                $d = array_key_exists(1, $a) && $a[1] !== null ? $this->num($a[1]) : 2.0;
                if ($v instanceof ExcelError || $d instanceof ExcelError) {
                    return ExcelError::value();
                }
                $noCommas = array_key_exists(2, $a) && $this->truthy($a[2]);
                $d = (int) $d;
                $v = self::roundHalfAway($v, $d);

                return number_format($v, max(0, $d), '.', $noCommas ? '' : ',');
        }

        // Numeric functions.
        $v = $this->num($x);
        if ($v instanceof ExcelError) {
            return $v;
        }
        switch ($name) {
            case 'ABS':
                return abs($v);
            case 'COS':
                return cos($v);
            case 'SIN':
                return sin($v);
            case 'TAN':
                return tan($v);
            case 'RADIANS':
                return $v * M_PI / 180;
            case 'DEGREES':
                return $v * 180 / M_PI;
            case 'SQRT':
                return $v < 0 ? ExcelError::num() : sqrt($v);
            case 'EXP':
                return exp($v);
            case 'LN':
                return $v <= 0 ? ExcelError::num() : log($v);
            case 'INT':
                return floor($v);
        }
        $d = $this->num($a[1] ?? null);
        if ($d instanceof ExcelError) {
            return $d;
        }
        switch ($name) {
            case 'ROUND':
                return self::roundHalfAway($v, (int) $d);
            case 'ROUNDUP':
            case 'ROUNDDOWN':
                $m = 10 ** (int) $d;
                $y = abs($v) * $m;
                $f = floor($y);
                $frac = $y - $f;
                $eps = 1e-12 * max(1.0, $y);
                if ($frac < $eps) {
                    $res = $f;
                } elseif (1 - $frac < $eps) {
                    $res = $f + 1;
                } else {
                    $res = $name === 'ROUNDUP' ? $f + 1 : $f;
                }

                return ($v < 0 ? -1 : 1) * $res / $m;
            case 'MOD':
                if ($d == 0.0) {
                    return ExcelError::div0();
                }

                return $v - $d * floor($v / $d);
            case 'POWER':
                return $this->binary('^', $v, $d);
        }
        throw new CalcException("Funcția {$name}() nu este suportată de motorul de calcul.");
    }

    public static function roundHalfAway(float $v, int $digits): float
    {
        return round($v, $digits, PHP_ROUND_HALF_UP);
    }

    /** TEXT(): numeric patterns only (0, 0.00, #,##0.00, 0%, 0.0%). */
    public static function formatNumber(float $v, string $fmt): string
    {
        if ($fmt === '' || strcasecmp($fmt, 'General') === 0 || $fmt === '@') {
            return self::numToStr($v);
        }
        if (! preg_match('/^[#0,]*0(\.[0#]+)?%?$/', $fmt)) {
            throw new CalcException("Formatul TEXT «{$fmt}» nu este suportat de motorul de calcul.");
        }
        $percent = str_ends_with($fmt, '%');
        if ($percent) {
            $v *= 100;
        }
        $dot = strpos($fmt, '.');
        $decimals = $dot === false ? 0 : strlen(rtrim(substr($fmt, $dot + 1), '%'));
        $thousands = str_contains($dot === false ? $fmt : substr($fmt, 0, $dot), ',');
        $v = self::roundHalfAway($v, $decimals);
        if ($v == 0.0) {
            $v = 0.0;
        }

        return number_format($v, $decimals, '.', $thousands ? ',' : '').($percent ? '%' : '');
    }

    private static function median(array $nums): float
    {
        sort($nums);
        $n = count($nums);
        $mid = intdiv($n, 2);

        return $n % 2 ? $nums[$mid] : ($nums[$mid - 1] + $nums[$mid]) / 2;
    }

    private function fnIf(array $args): mixed
    {
        $cond = $this->evVal($args[0]);
        if (is_array($cond)) {
            $then = isset($args[1]) ? $this->evVal($args[1]) : true;
            $else = isset($args[2]) ? $this->evVal($args[2]) : false;
            $pick = function ($c, $pair) {
                if ($c instanceof ExcelError) {
                    return $c;
                }
                if (is_string($c)) {
                    return ExcelError::value();
                }

                return $this->truthy($c) ? $pair[0] : $pair[1];
            };
            $pairs = $this->broadcast(fn ($t, $e) => [$t, $e], $then, $else);
            $rows = max(count($cond), count($pairs));
            $cols = max(count($cond[0] ?? []), count($pairs[0] ?? []));
            $out = [];
            for ($i = 0; $i < $rows; $i++) {
                $row = [];
                for ($j = 0; $j < $cols; $j++) {
                    $c = $cond[count($cond) === 1 ? 0 : $i][count($cond[0]) === 1 ? 0 : $j] ?? null;
                    $p = $pairs[count($pairs) === 1 ? 0 : $i][count($pairs[0]) === 1 ? 0 : $j] ?? [ExcelError::na(), ExcelError::na()];
                    $row[] = $pick($c, $p);
                }
                $out[] = $row;
            }

            return $out;
        }
        if ($cond instanceof ExcelError) {
            return $cond;
        }
        if (is_string($cond)) {
            $u = strtoupper($cond);
            if ($u !== 'TRUE' && $u !== 'FALSE') {
                return ExcelError::value();
            }
        }
        if ($this->truthy($cond)) {
            return isset($args[1]) ? ($args[1][0] === 'm' ? 0.0 : $this->ev($args[1])) : true;
        }

        return isset($args[2]) ? ($args[2][0] === 'm' ? 0.0 : $this->ev($args[2])) : false;
    }

    private function fnAndOr(bool $isAnd, array $args): mixed
    {
        $result = $isAnd;
        $seen = false;
        $error = null;
        foreach ($args as $a) {
            $v = $this->ev($a);
            $fromRef = $v instanceof RangeRef || is_array($v);
            foreach ($this->flat($v) as $x) {
                if ($x instanceof ExcelError) {
                    $error ??= $x;

                    continue;
                }
                if ($x === null || is_string($x)) {
                    if (! $fromRef && is_string($x)) {
                        $u = strtoupper($x);
                        if ($u !== 'TRUE' && $u !== 'FALSE') {
                            $error ??= ExcelError::value();

                            continue;
                        }
                        $x = $u === 'TRUE';
                    } elseif (! $fromRef && $x === null) {
                        $x = false;
                    } else {
                        continue;
                    }
                }
                $seen = true;
                $t = $this->truthy($x);
                $result = $isAnd ? ($result && $t) : ($result || $t);
            }
        }
        if ($error) {
            return $error;
        }

        return $seen ? $result : ExcelError::value();
    }

    /** Numbers for SUM-like aggregates: text and booleans inside references are ignored, direct arguments are coerced. */
    private function numbers(array $args): array|ExcelError
    {
        $out = [];
        foreach ($args as $a) {
            $v = $a[0] === 'c' ? $this->ev($a) : $this->ev($a);
            if ($v instanceof RangeRef) {
                [$r1, $c1, $r2, $c2] = $this->clamp($v);
                for ($r = $r1; $r <= $r2; $r++) {
                    for ($c = $c1; $c <= $c2; $c++) {
                        $x = $this->cellValue($v->sheet, $r, $c);
                        if (is_float($x)) {
                            $out[] = $x;
                        } elseif ($x instanceof ExcelError) {
                            return $x;
                        }
                    }
                }
            } elseif (is_array($v)) {
                foreach ($v as $row) {
                    foreach ($row as $x) {
                        if (is_float($x)) {
                            $out[] = $x;
                        } elseif ($x instanceof ExcelError) {
                            return $x;
                        }
                    }
                }
            } else {
                $n = $this->num($v);
                if ($n instanceof ExcelError) {
                    return $n;
                }
                $out[] = $n;
            }
        }

        return $out;
    }

    private function fnCount(array $args): float
    {
        $n = 0;
        foreach ($args as $a) {
            $v = $this->ev($a);
            if ($v instanceof RangeRef || is_array($v)) {
                foreach ($this->flat($v) as $x) {
                    if (is_float($x)) {
                        $n++;
                    }
                }
            } elseif (is_float($v) || is_bool($v) || (is_string($v) && self::parseNumber($v) !== null)) {
                $n++;
            }
        }

        return (float) $n;
    }

    private function fnSumproduct(array $args): mixed
    {
        $arrays = [];
        foreach ($args as $a) {
            $v = $this->evArr($a);
            if ($v instanceof RangeRef) {
                $v = $this->toArray($v);
            } elseif (! is_array($v)) {
                $v = [[$v]];
            }
            $arrays[] = $v;
        }
        $rows = count($arrays[0]);
        $cols = count($arrays[0][0] ?? []);
        foreach ($arrays as $v) {
            if (count($v) !== $rows || count($v[0] ?? []) !== $cols) {
                return ExcelError::value();
            }
        }
        $sum = 0.0;
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                $p = 1.0;
                foreach ($arrays as $v) {
                    $x = $v[$i][$j];
                    if ($x instanceof ExcelError) {
                        return $x;
                    }
                    $p *= is_float($x) ? $x : 0.0;
                }
                $sum += $p;
            }
        }

        return $sum;
    }

    /**
     * COUNTIF(S) / SUMIF(S) / AVERAGEIFS / MAXIFS / MINIFS.
     *
     * @param  array  $pairs  [rangeNode, criterionNode, rangeNode, criterionNode, ...]
     */
    private function fnIfs(string $mode, ?array $valueNode, array $pairs, bool $reshape = false): mixed
    {
        $ranges = [];
        $criteria = [];
        $shape = null;
        for ($i = 0; $i + 1 < count($pairs); $i += 2) {
            $range = $this->evArr($pairs[$i]);
            if ($range instanceof ExcelError) {
                return $range;
            }
            $ranges[] = $this->flat($range);
            $crit = $this->evVal($pairs[$i + 1]);
            if (is_array($crit)) {
                $shape ??= $crit;
            }
            $criteria[] = $crit;
        }
        $n = count($ranges[0]);
        foreach ($ranges as $r) {
            if (count($r) !== $n) {
                return ExcelError::value();
            }
        }
        $values = null;
        if ($valueNode !== null) {
            $vr = $this->evArr($valueNode);
            if ($vr instanceof ExcelError) {
                return $vr;
            }
            if ($reshape && $vr instanceof RangeRef) {
                // SUMIF: the sum range takes the shape of the criteria range, anchored at its top-left cell.
                $cr = $this->evArr($pairs[0]);
                if ($cr instanceof RangeRef) {
                    $vr = new RangeRef($vr->sheet, $vr->r1, $vr->c1, $vr->r1 + $cr->rows() - 1, $vr->c1 + $cr->cols() - 1);
                }
            }
            $values = $this->flat($vr);
            if (count($values) !== $n) {
                return ExcelError::value();
            }
        }

        $one = function (array $crits) use ($mode, $ranges, $values, $n) {
            $matchers = array_map(fn ($c) => Criteria::matcher($c), $crits);
            $acc = [];
            $count = 0;
            for ($i = 0; $i < $n; $i++) {
                foreach ($matchers as $k => $m) {
                    if (! $m($ranges[$k][$i])) {
                        continue 2;
                    }
                }
                $count++;
                if ($values !== null) {
                    $x = $values[$i];
                    if ($x instanceof ExcelError) {
                        return $x;
                    }
                    if (is_float($x)) {
                        $acc[] = $x;
                    }
                }
            }

            return match ($mode) {
                'COUNT' => (float) $count,
                'SUM' => (float) array_sum($acc),
                'AVERAGE' => $acc === [] ? ExcelError::div0() : array_sum($acc) / count($acc),
                'MAX' => $acc === [] ? 0.0 : (float) max($acc),
                'MIN' => $acc === [] ? 0.0 : (float) min($acc),
            };
        };

        if ($shape === null) {
            return $one($criteria);
        }
        // Array criteria (array formulas): one result per criterion element.
        $out = [];
        foreach ($shape as $i => $row) {
            $o = [];
            foreach ($row as $j => $_) {
                $cur = [];
                foreach ($criteria as $c) {
                    $cur[] = is_array($c) ? ($c[count($c) === 1 ? 0 : $i][count($c[0]) === 1 ? 0 : $j] ?? null) : $c;
                }
                $o[] = $one($cur);
            }
            $out[] = $o;
        }

        return $out;
    }

    private function fnIndex(array $args): mixed
    {
        $src = $this->evArr($args[0]);
        if ($src instanceof ExcelError) {
            return $src;
        }
        $rowArg = isset($args[1]) && $args[1][0] !== 'm' ? $this->num($this->scalar($this->evVal($args[1]))) : null;
        $colArg = isset($args[2]) && $args[2][0] !== 'm' ? $this->num($this->scalar($this->evVal($args[2]))) : null;
        if ($rowArg instanceof ExcelError) {
            return $rowArg;
        }
        if ($colArg instanceof ExcelError) {
            return $colArg;
        }
        if ($src instanceof RangeRef) {
            $rows = $src->rows();
            $cols = $src->cols();
        } elseif (is_array($src)) {
            $rows = count($src);
            $cols = count($src[0] ?? []);
        } else {
            $src = [[$src]];
            $rows = $cols = 1;
        }
        $row = $rowArg === null ? 0 : (int) $rowArg;
        $col = $colArg === null ? 0 : (int) $colArg;
        if (! isset($args[2])) {
            // Single index on a one-row vector addresses the column.
            if ($rows === 1 && $cols > 1) {
                $col = $row;
                $row = 1;
            } elseif ($cols === 1) {
                $col = 1;
            }
        }
        if ($row < 0 || $col < 0 || $row > $rows || $col > $cols) {
            return ExcelError::ref();
        }
        if ($src instanceof RangeRef) {
            return new RangeRef(
                $src->sheet,
                $row === 0 ? $src->r1 : $src->r1 + $row - 1,
                $col === 0 ? $src->c1 : $src->c1 + $col - 1,
                $row === 0 ? $src->r2 : $src->r1 + $row - 1,
                $col === 0 ? $src->c2 : $src->c1 + $col - 1,
            );
        }
        if ($row > 0 && $col > 0) {
            return $src[$row - 1][$col - 1];
        }
        if ($row > 0) {
            return [$src[$row - 1]];
        }
        if ($col > 0) {
            return array_map(fn ($r) => [$r[$col - 1]], $src);
        }

        return $src;
    }

    private function fnMatch(array $args): mixed
    {
        $lookup = $this->scalar($this->evVal($args[0]));
        if ($lookup instanceof ExcelError) {
            return $lookup;
        }
        $arr = $this->evArr($args[1]);
        if ($arr instanceof ExcelError) {
            return $arr;
        }
        $type = isset($args[2]) && $args[2][0] !== 'm' ? $this->num($this->scalar($this->evVal($args[2]))) : 1.0;
        if ($type instanceof ExcelError) {
            return $type;
        }
        $pos = $this->lookupPosition($lookup, $this->flat($arr), (int) $type);

        return $pos === null ? ExcelError::na() : (float) ($pos + 1);
    }

    /** 0-based position of $lookup in $list. $type: 0 exact, 1 largest ≤ (ascending), -1 smallest ≥ (descending). */
    private function lookupPosition(mixed $lookup, array $list, int $type): ?int
    {
        if ($lookup === null) {
            $lookup = 0.0;
        }
        $kind = is_float($lookup) ? 1 : (is_string($lookup) ? 2 : 3);
        if ($type === 0) {
            if ($kind === 2 && strpbrk($lookup, '*?~') !== false) {
                $re = '/^'.Criteria::wildcardRegex($lookup).'$/iu';
                foreach ($list as $i => $x) {
                    if (is_string($x) && preg_match($re, $x)) {
                        return $i;
                    }
                }

                return null;
            }
            $needle = $kind === 2 ? mb_strtolower($lookup) : $lookup;
            foreach ($list as $i => $x) {
                if ($kind === 1 ? (is_float($x) && $this->cmp($x, $needle) === 0) : ($kind === 2 ? (is_string($x) && mb_strtolower($x) === $needle) : $x === $needle)) {
                    return $i;
                }
            }

            return null;
        }
        $found = null;
        foreach ($list as $i => $x) {
            $xk = is_float($x) ? 1 : (is_string($x) ? 2 : (is_bool($x) ? 3 : 0));
            if ($xk !== $kind) {
                continue;
            }
            $c = $this->cmp($x, $lookup);
            if ($type > 0) {
                if ($c <= 0) {
                    $found = $i;
                } else {
                    break;
                }
            } else {
                if ($c >= 0) {
                    $found = $i;
                } else {
                    break;
                }
            }
        }

        return $found;
    }

    private function fnVlookup(array $args): mixed
    {
        $lookup = $this->scalar($this->evVal($args[0]));
        if ($lookup instanceof ExcelError) {
            return $lookup;
        }
        $table = $this->evArr($args[1]);
        if ($table instanceof ExcelError) {
            return $table;
        }
        $col = $this->num($this->scalar($this->evVal($args[2])));
        if ($col instanceof ExcelError) {
            return $col;
        }
        $col = (int) $col;
        $approx = isset($args[3]) && $args[3][0] !== 'm' ? $this->truthy($this->scalar($this->evVal($args[3]))) : true;
        if ($table instanceof RangeRef) {
            if ($col < 1) {
                return ExcelError::value();
            }
            if ($col > $table->cols()) {
                return ExcelError::ref();
            }
            $first = $this->flat(new RangeRef($table->sheet, $table->r1, $table->c1, $table->r2, $table->c1));
            $pos = $this->lookupPosition($lookup, $first, $approx ? 1 : 0);
            if ($pos === null) {
                return ExcelError::na();
            }

            return $this->cellValue($table->sheet, $table->r1 + $pos, $table->c1 + $col - 1) ?? 0.0;
        }
        if (! is_array($table)) {
            return ExcelError::na();
        }
        if ($col < 1 || $col > count($table[0] ?? [])) {
            return ExcelError::ref();
        }
        $pos = $this->lookupPosition($lookup, array_column($table, 0), $approx ? 1 : 0);

        return $pos === null ? ExcelError::na() : ($table[$pos][$col - 1] ?? 0.0);
    }

    private function fnLookup(array $args): mixed
    {
        $lookup = $this->scalar($this->evVal($args[0]));
        if ($lookup instanceof ExcelError) {
            return $lookup;
        }
        $vector = $this->flat($this->evArr($args[1]));
        if ($lookup === null) {
            $lookup = 0.0;
        }
        $kind = is_float($lookup) ? 1 : (is_string($lookup) ? 2 : 3);
        $found = null;
        foreach ($vector as $i => $x) {
            $xk = is_float($x) ? 1 : (is_string($x) ? 2 : (is_bool($x) ? 3 : 0));
            if ($xk === $kind && $this->cmp($x, $lookup) <= 0) {
                $found = $i;
            }
        }
        if ($found === null) {
            return ExcelError::na();
        }
        if (! isset($args[2])) {
            return $vector[$found];
        }
        $result = $this->flat($this->evArr($args[2]));

        return array_key_exists($found, $result) ? ($result[$found] ?? 0.0) : ExcelError::na();
    }

    private function fnAggregate(array $args): mixed
    {
        $fn = $this->num($this->scalar($this->evVal($args[0])));
        $opt = $this->num($this->scalar($this->evVal($args[1])));
        if ($fn instanceof ExcelError || $opt instanceof ExcelError) {
            return ExcelError::value();
        }
        $ignoreErrors = in_array((int) $opt, [2, 3, 6, 7], true);
        $values = $this->flat($this->evArr($args[2]));
        $nums = [];
        $nonEmpty = 0;
        foreach ($values as $x) {
            if ($x instanceof ExcelError) {
                if (! $ignoreErrors) {
                    return $x;
                }

                continue;
            }
            if ($x !== null) {
                $nonEmpty++;
            }
            if (is_float($x)) {
                $nums[] = $x;
            }
        }
        $k = isset($args[3]) ? $this->num($this->scalar($this->evVal($args[3]))) : null;
        if ($k instanceof ExcelError) {
            return $k;
        }
        switch ((int) $fn) {
            case 1:
                return $nums === [] ? ExcelError::div0() : array_sum($nums) / count($nums);
            case 2:
                return (float) count($nums);
            case 3:
                return (float) $nonEmpty;
            case 4:
                return $nums === [] ? 0.0 : (float) max($nums);
            case 5:
                return $nums === [] ? 0.0 : (float) min($nums);
            case 9:
                return (float) array_sum($nums);
            case 12:
                return $nums === [] ? ExcelError::num() : self::median($nums);
            case 14:
            case 15:
                $k = (int) ceil((float) $k);
                if ($k < 1 || $k > count($nums)) {
                    return ExcelError::num();
                }
                (int) $fn === 14 ? rsort($nums) : sort($nums);

                return $nums[$k - 1];
        }
        throw new CalcException('AGGREGATE: funcția '.(int) $fn.' nu este suportată de motorul de calcul.');
    }
}
