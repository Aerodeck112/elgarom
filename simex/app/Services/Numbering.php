<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/** Gap-free yearly counters for document numbers (RFQ-2026-0001, OF-2026-0001 …). */
final class Numbering
{
    public static function next(string $name, string $prefix): string
    {
        $year = (int) now()->format('Y');

        return DB::transaction(function () use ($name, $prefix, $year) {
            $row = DB::table('sequences')->where('name', $name)->where('year', $year)->lockForUpdate()->first();
            if ($row === null) {
                DB::table('sequences')->insert(['name' => $name, 'year' => $year, 'value' => 1]);
                $value = 1;
            } else {
                $value = $row->value + 1;
                DB::table('sequences')->where('name', $name)->where('year', $year)->update(['value' => $value]);
            }

            return sprintf('%s-%d-%04d', $prefix, $year, $value);
        });
    }

    /** The number the next document would get, without reserving it (shown in the header). */
    public static function peek(string $name, string $prefix): string
    {
        $year = (int) now()->format('Y');
        $value = (int) DB::table('sequences')->where('name', $name)->where('year', $year)->value('value');

        return sprintf('%s-%d-%04d', $prefix, $year, $value + 1);
    }
}
