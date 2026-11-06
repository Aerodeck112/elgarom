<?php

namespace App\Support;

/**
 * Google tag configuration exposed to the browser.
 *
 * Only identifiers are accepted (validated by strict patterns) – never script snippets.
 * Exactly one delivery path is active to avoid double counting:
 *  - mode "gtm":    only the GTM container loads; GA4/Ads are configured inside GTM;
 *  - mode "direct": gtag.js loads GA4 (and Ads conversion) directly; GTM is not loaded.
 */
class TagConfig
{
    public const PATTERNS = [
        'ga4_id' => '/^G-[A-Z0-9]{6,14}$/',
        'gtm_id' => '/^GTM-[A-Z0-9]{4,10}$/',
        'ads_id' => '/^AW-\d{6,14}$/',
        'ads_lead_label' => '/^[A-Za-z0-9_\-]{4,40}$/',
        'gsc_verification' => '/^[A-Za-z0-9_\-]{10,100}$/',
    ];

    public static function valid(string $key, ?string $value): bool
    {
        return $value !== null && $value !== '' && (bool) preg_match(self::PATTERNS[$key], $value);
    }

    private static function id(string $key): ?string
    {
        $v = setting('google.'.$key);

        return self::valid($key, $v) ? $v : null;
    }

    public static function mode(): string
    {
        return setting('google.mode') === 'gtm' && self::id('gtm_id') ? 'gtm' : 'direct';
    }

    public static function hasAnalytics(): bool
    {
        return self::mode() === 'gtm' || self::id('ga4_id') !== null;
    }

    public static function hasMarketing(): bool
    {
        return self::mode() === 'gtm' || self::id('ads_id') !== null;
    }

    public static function needsConsent(): bool
    {
        return (self::mode() === 'gtm') || self::id('ga4_id') || self::id('ads_id');
    }

    public static function public(): array
    {
        $mode = self::mode();

        return [
            'mode' => $mode,
            'gtm' => $mode === 'gtm' ? self::id('gtm_id') : null,
            'ga4' => $mode === 'direct' ? self::id('ga4_id') : null,
            'ads' => $mode === 'direct' ? self::id('ads_id') : null,
            'adsLeadLabel' => $mode === 'direct' ? self::id('ads_lead_label') : null,
            'consentMode' => setting('cookies.consent_mode') === 'advanced' ? 'advanced' : 'basic',
            'consentVersion' => (int) setting('cookies.policy_version', 1),
            'needsConsent' => (bool) self::needsConsent(),
            'ai' => \App\Services\Ai\Assistant::publicStatus(),
        ];
    }
}
