<?php

namespace App\Calc\Engine;

use Closure;

/** Criteria of COUNTIF / SUMIFS and friends (">5", "<>", "abc*", 12 …). */
final class Criteria
{
    public static function matcher(mixed $criterion): Closure
    {
        if ($criterion instanceof ExcelError) {
            return fn ($x) => $x instanceof ExcelError && $x->code === $criterion->code;
        }
        if ($criterion === null) {
            $criterion = 0.0;
        }
        if (is_bool($criterion)) {
            return fn ($x) => $x === $criterion;
        }
        if (is_float($criterion)) {
            return fn ($x) => (is_float($x) && $x == $criterion) || (is_string($x) && Workbook::parseNumber($x) === $criterion);
        }

        $op = '=';
        $operand = (string) $criterion;
        if (preg_match('/^(<=|>=|<>|<|>|=)(.*)$/su', $operand, $m)) {
            $op = $m[1];
            $operand = $m[2];
        }

        if ($operand === '') {
            return match ($op) {
                // "" and "=" match blanks; "<>" matches anything that is not truly empty.
                '=' => (string) $criterion === '=' ? fn ($x) => $x === null : fn ($x) => $x === null || $x === '',
                '<>' => fn ($x) => $x !== null,
                default => fn ($x) => false,
            };
        }

        $number = Workbook::parseNumber($operand);
        if ($number !== null) {
            return match ($op) {
                '=' => fn ($x) => (is_float($x) && $x == $number) || (is_string($x) && Workbook::parseNumber($x) === $number),
                '<>' => fn ($x) => ! ((is_float($x) && $x == $number) || (is_string($x) && Workbook::parseNumber($x) === $number)),
                '<' => fn ($x) => is_float($x) && $x < $number,
                '>' => fn ($x) => is_float($x) && $x > $number,
                '<=' => fn ($x) => is_float($x) && $x <= $number,
                '>=' => fn ($x) => is_float($x) && $x >= $number,
            };
        }

        $upper = strtoupper($operand);
        if ($upper === 'TRUE' || $upper === 'FALSE') {
            $bool = $upper === 'TRUE';

            return $op === '<>' ? fn ($x) => $x !== $bool : fn ($x) => $x === $bool;
        }

        if ($op === '=' || $op === '<>') {
            $re = '/^'.self::wildcardRegex($operand).'$/isu';
            $eq = fn ($x) => is_string($x) && preg_match($re, $x) === 1;

            return $op === '=' ? $eq : fn ($x) => ! $eq($x);
        }

        $needle = mb_strtolower($operand);

        return fn ($x) => is_string($x) && match ($op) {
            '<' => mb_strtolower($x) < $needle,
            '>' => mb_strtolower($x) > $needle,
            '<=' => mb_strtolower($x) <= $needle,
            '>=' => mb_strtolower($x) >= $needle,
        };
    }

    /** Excel wildcards (* ? ~) → body of a PCRE pattern. */
    public static function wildcardRegex(string $pattern): string
    {
        $out = '';
        $chars = preg_split('//u', $pattern, -1, PREG_SPLIT_NO_EMPTY);
        for ($i = 0, $n = count($chars); $i < $n; $i++) {
            $ch = $chars[$i];
            if ($ch === '~' && $i + 1 < $n && in_array($chars[$i + 1], ['*', '?', '~'], true)) {
                $out .= preg_quote($chars[++$i], '/');
            } elseif ($ch === '*') {
                $out .= '.*';
            } elseif ($ch === '?') {
                $out .= '.';
            } else {
                $out .= preg_quote($ch, '/');
            }
        }

        return $out;
    }
}
