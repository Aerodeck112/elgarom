<?php

use App\Models\Setting;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('md')) {
    /** Markdown to HTML with raw HTML stripped and unsafe links removed. */
    function md(?string $text): HtmlString
    {
        if ($text === null || trim($text) === '') {
            return new HtmlString('');
        }

        return new HtmlString(Str::markdown($text, ['html_input' => 'strip', 'allow_unsafe_links' => false, 'max_nesting_level' => 10]));
    }
}

if (! function_exists('csp_nonce')) {
    function csp_nonce(): string
    {
        return app()->bound('csp.nonce') ? app('csp.nonce') : '';
    }
}

if (! function_exists('safe_url')) {
    /** Only http(s), mailto, tel, relative and anchor links are rendered. */
    function safe_url(?string $url, string $fallback = '#'): string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return $fallback;
        }
        if (preg_match('#^(https?://|mailto:|tel:|/|\#)#i', $url) && ! str_starts_with($url, '//')) {
            return $url;
        }

        return $fallback;
    }
}

if (! function_exists('money')) {
    /** 1234.5 → "1.234,50" (Romanian notation). Null stays an em dash so a missing figure is never shown as zero. */
    function money(float|int|string|null $value, int $decimals = 2): string
    {
        return $value === null || $value === '' ? '—' : number_format((float) $value, $decimals, ',', '.');
    }
}

if (! function_exists('qty')) {
    function qty(float|int|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        $v = (float) $value;

        return $v == floor($v) ? number_format($v, 0, ',', '.') : rtrim(rtrim(number_format($v, 3, ',', '.'), '0'), ',');
    }
}

if (! function_exists('can')) {
    /** Permission of the signed-in user (false for guests). */
    function can(string $permission): bool
    {
        return (bool) auth()->user()?->can_($permission);
    }
}

if (! function_exists('cron_command')) {
    /** The cron line for this server, with the command-line PHP that matches the website's PHP. */
    function cron_command(): string
    {
        require_once base_path('bootstrap/php_cli.php');

        return '* * * * * '.simex_cli_php()[0].' '.base_path('artisan').' schedule:run >/dev/null 2>&1';
    }
}
