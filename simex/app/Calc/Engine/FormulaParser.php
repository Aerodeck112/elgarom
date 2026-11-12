<?php

namespace App\Calc\Engine;

/**
 * Excel formula text → AST (nested PHP arrays, cheap to cache with var_export).
 *
 * Node shapes:
 *   ['n', float]  ['s', string]  ['b', bool]  ['e', '#N/A']  ['m'] (missing argument)
 *   ['c', sheet, row, col, absRow, absCol]                      single cell (sheet -1 = formula's own sheet)
 *   ['r', sheet, r1, c1, r2, c2, absR1, absC1, absR2, absC2]    rectangular range
 *   ['nm', 'NAME']                                             defined name
 *   ['op', '+', left, right]  ['u', '-', operand]  ['pct', operand]
 *   ['rng', left, right]                                       range built from two expressions (INDEX(..):INDEX(..))
 *   ['f', 'NAME', [args]]                                      function call
 *   ['a', [[literal, ...], ...]]                               array constant
 */
final class FormulaParser
{
    private const BP = ['=' => 10, '<>' => 10, '<' => 10, '>' => 10, '<=' => 10, '>=' => 10, '&' => 20, '+' => 30, '-' => 30, '*' => 40, '/' => 40, '^' => 50, ':' => 80];

    private string $src = '';

    private int $pos = 0;

    private int $len = 0;

    /** @var array{0:string,1:mixed}|null current token */
    private ?array $tok = null;

    /** @param array<string,int> $sheets upper-cased sheet name → index */
    public function __construct(private readonly array $sheets) {}

    public function parse(string $formula): array
    {
        $this->src = ltrim($formula);
        if (str_starts_with($this->src, '=')) {
            $this->src = substr($this->src, 1);
        }
        $this->len = strlen($this->src);
        $this->pos = 0;
        $this->next();
        $node = $this->expr(0);
        if ($this->tok[0] !== 'eof') {
            throw new CalcException('Formulă neînțeleasă lângă poziția '.$this->pos.': '.$formula);
        }

        return $node;
    }

    public static function colToNum(string $letters): int
    {
        $n = 0;
        foreach (str_split(strtoupper($letters)) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return $n;
    }

    public static function numToCol(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m).$s;
            $n = intdiv($n - 1, 26);
        }

        return $s;
    }

    private function expr(int $rbp): array
    {
        $left = $this->prefix();
        while (true) {
            [$type, $val] = $this->tok;
            if ($type === 'op' && $val === '%') {
                $this->next();
                $left = ['pct', $left];

                continue;
            }
            if ($type !== 'op' || ! isset(self::BP[$val]) || self::BP[$val] <= $rbp) {
                break;
            }
            $this->next();
            $right = $this->expr(self::BP[$val]);
            if ($val === ':') {
                $left = $this->makeRange($left, $right);
            } else {
                $left = ['op', $val, $left, $right];
            }
        }

        return $left;
    }

    private function makeRange(array $l, array $r): array
    {
        if ($l[0] === 'c' && $r[0] === 'c' && ($r[1] === -1 || $r[1] === $l[1])) {
            return ['r', $l[1], $l[2], $l[3], $r[2], $r[3], $l[4], $l[5], $r[4], $r[5]];
        }

        return ['rng', $l, $r];
    }

    private function prefix(): array
    {
        [$type, $val] = $this->tok;
        switch ($type) {
            case 'num':
                $this->next();

                return ['n', $val];
            case 'str':
                $this->next();

                return ['s', $val];
            case 'bool':
                $this->next();

                return ['b', $val];
            case 'err':
                $this->next();

                return ['e', $val];
            case 'node':
                $this->next();

                return $val;
            case 'func':
                $this->next();

                return ['f', $val, $this->args()];
            case 'op':
                if ($val === '-' || $val === '+') {
                    $this->next();
                    $operand = $this->expr(55);

                    return $val === '-' ? ['u', '-', $operand] : ['u', '+', $operand];
                }
                if ($val === '(') {
                    $this->next();
                    $inner = $this->expr(0);
                    $this->expect(')');

                    return $inner;
                }
                if ($val === '{') {
                    return $this->arrayConst();
                }
        }
        throw new CalcException('Formulă neînțeleasă lângă poziția '.$this->pos.': '.$this->src);
    }

    /** Parses "arg, arg, ...)" – the opening parenthesis was consumed by the lexer. */
    private function args(): array
    {
        $args = [];
        if ($this->tok[0] === 'op' && $this->tok[1] === ')') {
            $this->next();

            return $args;
        }
        while (true) {
            if ($this->tok[0] === 'op' && ($this->tok[1] === ',' || $this->tok[1] === ')')) {
                $args[] = ['m'];
            } else {
                $args[] = $this->expr(0);
            }
            if ($this->tok[0] === 'op' && $this->tok[1] === ',') {
                $this->next();

                continue;
            }
            $this->expect(')');

            return $args;
        }
    }

    private function arrayConst(): array
    {
        $rows = [[]];
        $this->next();
        while (true) {
            [$type, $val] = $this->tok;
            $sign = 1;
            if ($type === 'op' && ($val === '-' || $val === '+')) {
                $sign = $val === '-' ? -1 : 1;
                $this->next();
                [$type, $val] = $this->tok;
            }
            $rows[count($rows) - 1][] = match ($type) {
                'num' => $sign * $val,
                'str', 'bool' => $val,
                'err' => ExcelError::of($val),
                default => throw new CalcException('Constantă matrice invalidă: '.$this->src),
            };
            $this->next();
            if ($this->tok[0] === 'op' && $this->tok[1] === ',') {
                $this->next();
            } elseif ($this->tok[0] === 'op' && $this->tok[1] === ';') {
                $rows[] = [];
                $this->next();
            } elseif ($this->tok[0] === 'op' && $this->tok[1] === '}') {
                $this->next();

                return ['a', $rows];
            } else {
                throw new CalcException('Constantă matrice invalidă: '.$this->src);
            }
        }
    }

    private function expect(string $op): void
    {
        if ($this->tok[0] !== 'op' || $this->tok[1] !== $op) {
            throw new CalcException("Se aștepta «{$op}» lângă poziția {$this->pos}: {$this->src}");
        }
        $this->next();
    }

    private function next(): void
    {
        $s = $this->src;
        while ($this->pos < $this->len && ($s[$this->pos] === ' ' || $s[$this->pos] === "\n" || $s[$this->pos] === "\r" || $s[$this->pos] === "\t")) {
            $this->pos++;
        }
        if ($this->pos >= $this->len) {
            $this->tok = ['eof', null];

            return;
        }
        $ch = $s[$this->pos];

        if ($ch === '"') {
            $i = $this->pos + 1;
            $out = '';
            while (true) {
                $j = strpos($s, '"', $i);
                if ($j === false) {
                    throw new CalcException('Șir neînchis: '.$s);
                }
                $out .= substr($s, $i, $j - $i);
                if ($j + 1 < $this->len && $s[$j + 1] === '"') {
                    $out .= '"';
                    $i = $j + 2;

                    continue;
                }
                $this->pos = $j + 1;
                break;
            }
            $this->tok = ['str', $out];

            return;
        }

        $digit = $ch >= '0' && $ch <= '9';
        // A sheet name may start with a digit (LibreOffice writes 14_SHEET!A1 unquoted).
        if ($digit && preg_match('/\G[A-Za-z0-9_.\x80-\xff]+!/', $s, $m, 0, $this->pos)) {
            $digit = false;
        }
        if ($digit || ($ch === '.' && $this->pos + 1 < $this->len && ctype_digit($s[$this->pos + 1]))) {
            // A leading digit may also start a row range (5:7) – not used by the supported models.
            if (preg_match('/\G(\d+\.?\d*|\.\d+)([eE][+-]?\d+)?/', $s, $m, 0, $this->pos)) {
                $this->pos += strlen($m[0]);
                $this->tok = ['num', (float) $m[0]];

                return;
            }
        }

        if ($ch === '#') {
            if (preg_match('/\G#(N\/A|VALUE!|REF!|DIV\/0!|NAME\?|NUM!|NULL!)/', $s, $m, 0, $this->pos)) {
                $this->pos += strlen($m[0]);
                $this->tok = ['err', $m[0]];

                return;
            }
        }

        $two = substr($s, $this->pos, 2);
        if ($two === '<>' || $two === '<=' || $two === '>=') {
            $this->pos += 2;
            $this->tok = ['op', $two];

            return;
        }
        if (strpos('+-*/^&=<>%:,(){};', $ch) !== false) {
            $this->pos++;
            $this->tok = ['op', $ch];

            return;
        }

        // Optional sheet prefix.
        $sheet = -1;
        if ($ch === "'") {
            if (! preg_match("/\G'((?:[^']|'')+)'!/", $s, $m, 0, $this->pos)) {
                throw new CalcException('Referință de foaie invalidă: '.$s);
            }
            $this->pos += strlen($m[0]);
            $sheet = $this->sheets[mb_strtoupper(str_replace("''", "'", $m[1]))] ?? -2;
        } elseif (preg_match('/\G([A-Za-z0-9_.\x80-\xff]+)!/', $s, $m, 0, $this->pos)) {
            $this->pos += strlen($m[0]);
            $sheet = $this->sheets[mb_strtoupper($m[1])] ?? -2;
        }

        // Cell reference.
        if (preg_match('/\G(\$?)([A-Za-z]{1,3})(\$?)(\d{1,7})(?![A-Za-z0-9_.(])/', $s, $m, 0, $this->pos)) {
            $this->pos += strlen($m[0]);
            $this->tok = ['node', $sheet === -2 ? ['e', '#REF!'] : ['c', $sheet, (int) $m[4], self::colToNum($m[2]), $m[3] === '$', $m[1] === '$']];

            return;
        }
        // Whole-column reference (A:C).
        if (preg_match('/\G(\$?)([A-Za-z]{1,3}):(\$?)([A-Za-z]{1,3})(?![A-Za-z0-9_.(])/', $s, $m, 0, $this->pos)) {
            $this->pos += strlen($m[0]);
            $this->tok = ['node', $sheet === -2 ? ['e', '#REF!'] : ['r', $sheet, 1, self::colToNum($m[2]), 1048576, self::colToNum($m[4]), true, $m[1] === '$', true, $m[3] === '$']];

            return;
        }
        // Function name or defined name / boolean.
        if (preg_match('/\G([A-Za-z_\\\\\x80-\xff][A-Za-z0-9_.\x80-\xff]*)(\s*\()?/', $s, $m, 0, $this->pos)) {
            $this->pos += strlen($m[0]);
            $word = strtoupper($m[1]);
            if (! empty($m[2])) {
                if (str_starts_with($word, '_XLFN.')) {
                    $word = substr($word, 6);
                }
                if (str_starts_with($word, '_XLWS.')) {
                    $word = substr($word, 6);
                }
                $this->tok = ['func', $word];

                return;
            }
            if ($word === 'TRUE' || $word === 'FALSE') {
                $this->tok = ['bool', $word === 'TRUE'];

                return;
            }
            $this->tok = ['node', ['nm', $word]];

            return;
        }

        throw new CalcException('Caracter neașteptat «'.$ch.'» la poziția '.$this->pos.': '.$s);
    }
}
