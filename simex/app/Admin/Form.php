<?php

namespace App\Admin;

use Illuminate\Validation\Rule;

/**
 * Declarative form fields shared by the generic resource screens and the settings screens.
 * A field: name, label, type (text|email|url|number|decimal|date|select|textarea|checkbox|secret|lines),
 * optional rules, options, hint, max, span (grid columns 3–12), perm (permission needed to edit it).
 */
final class Form
{
    /** @return array<string, mixed> validation rules keyed by field name */
    public static function rules(array $fields, ?string $table = null, ?int $ignoreId = null): array
    {
        $rules = [];
        foreach ($fields as $f) {
            if (isset($f['perm']) && ! can($f['perm'])) {
                continue;
            }
            $r = $f['rules'] ?? null;
            if ($r === null) {
                $r = match ($f['type']) {
                    'email' => ['nullable', 'email', 'max:190'],
                    'url' => ['nullable', 'url:http,https', 'max:255'],
                    'number' => ['nullable', 'integer', 'min:0', 'max:2000000000'],
                    'decimal' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
                    'date' => ['nullable', 'date'],
                    'select' => ['nullable', Rule::in(array_map('strval', array_keys(self::options($f))))],
                    'checkbox' => ['nullable', 'boolean'],
                    'textarea', 'lines' => ['nullable', 'string', 'max:'.($f['max'] ?? 5000)],
                    'secret' => ['nullable', 'string', 'max:500'],
                    default => ['nullable', 'string', 'max:'.($f['max'] ?? 255)],
                };
            } elseif (is_string($r)) {
                $r = explode('|', $r);
            }
            foreach ($r as $i => $rule) {
                if ($rule === 'unique' && $table !== null) {
                    $r[$i] = Rule::unique($table, $f['name'])->ignore($ignoreId);
                }
            }
            if ($f['type'] === 'decimal') {
                // Romanian users type a decimal comma; it is normalised before validation by the controller.
                $r = array_values(array_unique(array_merge($r, ['numeric'])));
            }
            $rules[$f['name']] = $r;
        }

        return $rules;
    }

    /** Request input with decimal commas turned into points for decimal fields. */
    public static function normalise(array $fields, array $input): array
    {
        foreach ($fields as $f) {
            if ($f['type'] === 'decimal' && isset($input[$f['name']]) && is_string($input[$f['name']])) {
                $input[$f['name']] = str_replace([' ', ','], ['', '.'], trim($input[$f['name']]));
            }
            if ($f['type'] === 'checkbox') {
                $input[$f['name']] = ! empty($input[$f['name']]);
            }
        }

        return $input;
    }

    public static function options(array $field): array
    {
        $o = $field['options'] ?? [];

        return $o instanceof \Closure ? $o() : $o;
    }
}
