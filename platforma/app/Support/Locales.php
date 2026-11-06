<?php

namespace App\Support;

use App\Models\Setting;

class Locales
{
    public static function all(): array
    {
        return config('elgarom.locales');
    }

    public static function default(): string
    {
        return config('elgarom.default_locale', 'ro');
    }

    /** Enabled locales (default locale always first and always enabled). */
    public static function enabled(): array
    {
        $codes = (array) Setting::get('locales.enabled', [self::default()]);
        $out = [self::default()];
        foreach (array_keys(self::all()) as $c) {
            if ($c !== self::default() && in_array($c, $codes, true)) {
                $out[] = $c;
            }
        }

        return $out;
    }

    public static function isEnabled(string $code): bool
    {
        return in_array($code, self::enabled(), true);
    }

    public static function prefix(?string $code = null): string
    {
        $p = self::all()[$code ?? app()->getLocale()]['prefix'] ?? '';

        return $p === '' ? '' : '/'.$p;
    }

    public static function segment(string $name, ?string $code = null): string
    {
        $code ??= app()->getLocale();

        return self::all()[$code]['segments'][$name] ?? self::all()[self::default()]['segments'][$name];
    }

    public static function routeName(string $name, ?string $code = null): string
    {
        $code ??= app()->getLocale();

        return $code === self::default() ? $name : $code.'.'.$name;
    }

    /** Locale from a URL path such as /de/... (used where no route middleware ran, e.g. 404 pages). */
    public static function fromPath(string $path): string
    {
        $first = explode('/', trim($path, '/'))[0] ?? '';
        foreach (self::all() as $code => $l) {
            if ($l['prefix'] !== '' && $l['prefix'] === $first) {
                return $code;
            }
        }

        return self::default();
    }
}
