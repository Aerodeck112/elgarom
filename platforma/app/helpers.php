<?php

use App\Models\Media;
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
    /**
     * Markdown to HTML with raw HTML stripped and unsafe links (javascript:, data:) removed.
     * Content editors cannot inject scripts through markdown fields.
     */
    function md(?string $text): HtmlString
    {
        if ($text === null || trim($text) === '') {
            return new HtmlString('');
        }

        return new HtmlString(Str::markdown($text, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 10,
        ]));
    }
}

if (! function_exists('legal_md')) {
    /** Markdown for legal pages, with company placeholders filled from settings (escaped). */
    function legal_md(?string $text): HtmlString
    {
        $company = trim((string) (setting('company.legal_name') ?: setting('company.name', 'ELGAROM')));
        $extra = array_filter([setting('company.cui') ? 'CUI '.setting('company.cui') : null, setting('company.reg_com') ? 'Nr. Reg. Com. '.setting('company.reg_com') : null]);
        if ($extra) {
            $company .= ' ('.implode(', ', $extra).')';
        }
        $map = [
            '{{company}}' => $company,
            '{{address}}' => company_address(),
            '{{email}}' => company_email(),
        ];
        $text = strtr((string) $text, array_map(fn ($v) => str_replace(['*', '_', '[', ']', '<', '>'], '', (string) $v), $map));

        return md($text);
    }
}

if (! function_exists('media_item')) {
    function media_item($id): ?Media
    {
        static $cache = [];
        if (! $id) {
            return null;
        }
        if ($id instanceof Media) {
            return $id;
        }

        return $cache[$id] ??= Media::find($id);
    }
}

if (! function_exists('company_address')) {
    function company_address(): string
    {
        return collect([setting('company.street'), setting('company.city'), setting('company.county') ? 'jud. '.setting('company.county') : null])
            ->filter()->implode(', ');
    }
}

if (! function_exists('company_email')) {
    function company_email(): string
    {
        $emails = setting('company.emails', []);

        return $emails[0]['email'] ?? 'office@elgarom.ro';
    }
}

if (! function_exists('tel_href')) {
    function tel_href(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number);
        if (str_starts_with($digits, '0040')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = '40'.substr($digits, 1);
        }

        return 'tel:+'.$digits;
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

if (! function_exists('lurl')) {
    /** URL in the given (or current) locale: lurl('/x') → /de/x */
    function lurl(string $path = '/', ?string $locale = null): string
    {
        $prefix = \App\Support\Locales::prefix($locale);
        $path = '/'.ltrim($path, '/');

        return url($prefix === '' ? $path : ($path === '/' ? $prefix : $prefix.$path));
    }
}

if (! function_exists('lroute')) {
    function lroute(string $name, array $params = [], ?string $locale = null): string
    {
        return route(\App\Support\Locales::routeName($name, $locale), $params);
    }
}

if (! function_exists('page_url')) {
    /** URL of a system page by its translation key (Romanian slug), in the current locale; falls back to Romanian. */
    function page_url(string $key, ?string $locale = null): string
    {
        static $cache = [];
        $locale ??= app()->getLocale();
        $k = $locale.'|'.$key;
        if (! isset($cache[$k])) {
            $page = \App\Models\Page::where('translation_key', $key)->where('locale', $locale)->where('status', 'published')->first()
                ?? \App\Models\Page::where('translation_key', $key)->where('locale', \App\Support\Locales::default())->first();
            $cache[$k] = $page ? $page->url() : lurl('/', $locale);
        }

        return $cache[$k];
    }
}

if (! function_exists('service_url')) {
    function service_url($service): string
    {
        return lurl('/'.\App\Support\Locales::segment('services', $service->locale).'/'.$service->slug, $service->locale);
    }
}

if (! function_exists('project_url')) {
    function project_url($project): string
    {
        return lurl('/'.\App\Support\Locales::segment('projects', $project->locale).'/'.$project->slug, $project->locale);
    }
}

if (! function_exists('lsetting')) {
    /** Translatable setting: "key.de" for German, falling back to "key". */
    function lsetting(string $key, mixed $default = null): mixed
    {
        $loc = app()->getLocale();
        if ($loc !== \App\Support\Locales::default()) {
            $v = setting($key.'.'.$loc);
            if ($v !== null && $v !== '') {
                return $v;
            }
        }

        return setting($key, $default);
    }
}
