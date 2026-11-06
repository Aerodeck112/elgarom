<?php

namespace App\Admin;

use Illuminate\Http\Request;

/** Builds validation rules and normalized values from field definitions (resources, sections, settings). */
class FormData
{
    /** Rules for a list of fields under an optional prefix ("data."). */
    public static function rules(array $fields, string $prefix = ''): array
    {
        $rules = [];
        foreach ($fields as $f) {
            $key = $prefix.$f['name'];
            $r = $f['rules'] ?? null;
            switch ($f['type']) {
                case 'checkbox':
                    $rules[$key] = 'nullable|boolean';
                    break;
                case 'repeater':
                    $rules[$key] = 'nullable|array|max:40';
                    foreach ($f['fields'] as $sf) {
                        $rules[$key.'.*.'.$sf['name']] = self::defaultRule($sf);
                    }
                    break;
                case 'media_multi':
                    $rules[$key] = $r ?? 'nullable|array|max:60';
                    $rules[$key.'.*'] = $f['item_rules'] ?? 'integer|exists:media,id';
                    break;
                default:
                    $rules[$key] = $r ?? self::defaultRule($f);
            }
        }

        return $rules;
    }

    private static function defaultRule(array $f): string|array
    {
        $max = $f['max'] ?? null;

        return match ($f['type']) {
            'media' => 'nullable|integer|exists:media,id',
            'url' => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/|\/|mailto:|tel:|#)/'],
            'select' => 'nullable|in:'.implode(',', array_keys($f['options'] ?? [])),
            'number' => 'nullable|numeric',
            'markdown', 'textarea' => 'nullable|string|max:'.($max ?? 20000),
            default => 'nullable|string|max:'.($max ?? 500),
        };
    }

    /** Extract normalized values for the given fields from validated input. */
    public static function values(array $fields, array $input): array
    {
        $out = [];
        foreach ($fields as $f) {
            $n = $f['name'];
            if ($f['type'] === 'file') {
                continue;
            }
            $v = $input[$n] ?? null;
            $out[$n] = match ($f['type']) {
                'checkbox' => (bool) $v,
                'media' => $v ? (int) $v : null,
                'media_multi' => array_values(array_map('intval', array_filter((array) $v))),
                'repeater' => array_values(array_filter(array_map(
                    fn ($row) => array_intersect_key((array) $row, array_flip(array_column($f['fields'], 'name'))),
                    (array) $v
                ), fn ($row) => implode('', array_map('strval', $row)) !== '')),
                'number' => $v === null || $v === '' ? ($n === 'sort' ? 0 : null) : $v + 0,
                default => is_string($v) ? trim($v) : $v,
            };
        }

        return $out;
    }

    public static function checkboxesFromRequest(array $fields, Request $request, array $validated, string $prefix = ''): array
    {
        foreach ($fields as $f) {
            if ($f['type'] === 'checkbox') {
                $validated[$f['name']] = $request->boolean($prefix.$f['name']);
            }
        }

        return $validated;
    }
}
