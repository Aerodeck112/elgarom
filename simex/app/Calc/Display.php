<?php

namespace App\Calc;

use App\Calc\Engine\ExcelError;
use App\Calc\Engine\Workbook;

/** Formats engine values for people, following the cell's Excel number format where it is a simple one. */
final class Display
{
    public static function format(mixed $v, ?string $fmt = null): string
    {
        if ($v === null) {
            return '';
        }
        if (is_string($v)) {
            return $v;
        }
        if (is_bool($v)) {
            return $v ? 'DA' : 'NU';
        }
        if ($v instanceof ExcelError) {
            return $v->code;
        }
        $v = (float) $v;
        if ($fmt !== null) {
            $clean = preg_replace('/\[[^\]]*\]|"[^"]*"|\\\\.|[_*]./u', '', explode(';', $fmt)[0]);
            if (preg_match('/[dy]/i', $clean) && ! preg_match('/[0#]/', $clean)) {
                return $v > 0 ? gmdate('d.m.Y', (int) round(($v - 25569) * 86400)) : '';
            }
            if (preg_match('/([#0,]*0)(\.([0#]+))?\s*(%?)/', $clean, $m)) {
                $decimals = strlen($m[3] ?? '');
                $percent = ($m[4] ?? '') === '%';

                return self::number($percent ? $v * 100 : $v, $decimals, str_contains($m[1], ',') || abs($v) >= 10000).($percent ? ' %' : '');
            }
        }
        if ($v == floor($v) && abs($v) < 1e12) {
            return self::number($v, 0, abs($v) >= 10000);
        }
        $abs = abs($v);
        $decimals = $abs >= 100 ? 2 : ($abs >= 1 ? 3 : 4);

        return rtrim(rtrim(self::number($v, $decimals, $abs >= 10000), '0'), ',');
    }

    public static function number(float $v, int $decimals, bool $grouping = true): string
    {
        return number_format($v, $decimals, ',', $grouping ? '.' : '');
    }

    /** JSON-safe raw value. */
    public static function raw(mixed $v): mixed
    {
        if ($v instanceof ExcelError) {
            return $v->code;
        }

        return $v;
    }

    public static function numberOrNull(mixed $v): ?float
    {
        return is_float($v) || is_int($v) ? (float) $v : null;
    }

    public static function text(mixed $v): string
    {
        return is_string($v) ? $v : ($v === null ? '' : ($v instanceof ExcelError ? $v->code : (is_bool($v) ? ($v ? 'DA' : 'NU') : Workbook::numToStr((float) $v))));
    }
}
