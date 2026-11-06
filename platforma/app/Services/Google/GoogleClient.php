<?php

namespace App\Services\Google;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal Google OAuth 2.0 + REST client (no SDK dependency). Read-only scopes except
 * Search Console sitemap submission. Never modifies Ads budgets or publishes campaigns.
 */
class GoogleClient
{
    public const SCOPES = [
        'gsc' => ['https://www.googleapis.com/auth/webmasters', 'Search Console (citire + trimitere sitemap)'],
        'ga4' => ['https://www.googleapis.com/auth/analytics.readonly', 'Google Analytics 4 (doar citire)'],
        'gbp' => ['https://www.googleapis.com/auth/business.manage', 'Google Business Profile'],
        'ads' => ['https://www.googleapis.com/auth/adwords', 'Google Ads (folosit doar pentru rapoarte)'],
    ];

    public static function configured(): bool
    {
        return Setting::has('google.oauth_client_id') && Setting::has('google.oauth_client_secret');
    }

    public static function connected(): bool
    {
        return Setting::has('google.oauth_refresh_token');
    }

    public static function granted(): array
    {
        return (array) setting('google.oauth_scopes', []);
    }

    public static function has(string $service): bool
    {
        return in_array(self::SCOPES[$service][0], self::granted(), true);
    }

    /** Overall connection state shown in the admin. */
    public static function state(): array
    {
        if (! self::configured()) {
            return ['neconfigurat', 'Neconfigurat', 'Creați un client OAuth în Google Cloud Console și introduceți ID-ul și secretul.'];
        }
        if (! self::connected()) {
            return ['neconectat', 'Neconectat', 'Apăsați „Conectează contul Google” și acceptați permisiunile.'];
        }
        if (setting('google.oauth_error') === 'invalid_grant') {
            return ['expirat', 'Autorizare expirată', 'Google a revocat sau a expirat autorizarea. Reconectați contul.'];
        }

        return ['conectat', 'Conectat', 'Cont: '.(setting('google.oauth_email') ?: 'necunoscut')];
    }

    public static function redirectUri(): string
    {
        return route('admin.google.callback');
    }

    public static function authUrl(array $services, string $state): string
    {
        $scopes = ['openid', 'email'];
        foreach ($services as $s) {
            if (isset(self::SCOPES[$s])) {
                $scopes[] = self::SCOPES[$s][0];
            }
        }

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => setting('google.oauth_client_id'),
            'redirect_uri' => self::redirectUri(),
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);
    }

    public static function exchange(string $code): void
    {
        $r = Http::asForm()->timeout(20)->post('https://oauth2.googleapis.com/token', [
            'code' => $code, 'client_id' => setting('google.oauth_client_id'), 'client_secret' => setting('google.oauth_client_secret'),
            'redirect_uri' => self::redirectUri(), 'grant_type' => 'authorization_code',
        ]);
        if (! $r->successful()) {
            throw new RuntimeException('Google a refuzat schimbul codului: '.($r->json('error_description') ?? $r->json('error') ?? $r->status()));
        }
        $t = $r->json();
        if (empty($t['refresh_token'])) {
            throw new RuntimeException('Google nu a returnat un refresh token. Revocați accesul aplicației din contul Google și conectați din nou.');
        }
        Setting::put('google.oauth_refresh_token', $t['refresh_token'], true);
        Setting::put('google.oauth_access', ['token' => $t['access_token'], 'expires' => time() + (int) $t['expires_in'] - 60], true);
        Setting::put('google.oauth_scopes', explode(' ', $t['scope'] ?? ''));
        Setting::forget('google.oauth_error');
        $email = null;
        if (! empty($t['id_token'])) {
            $parts = explode('.', $t['id_token']);
            $payload = json_decode(base64_decode(strtr($parts[1] ?? '', '-_', '+/')), true);
            $email = $payload['email'] ?? null;
        }
        Setting::put('google.oauth_email', $email);
    }

    public static function disconnect(): void
    {
        $rt = setting('google.oauth_refresh_token');
        if ($rt) {
            try {
                Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/revoke', ['token' => $rt]);
            } catch (\Throwable) {
            }
        }
        foreach (['google.oauth_refresh_token', 'google.oauth_access', 'google.oauth_scopes', 'google.oauth_email', 'google.oauth_error', 'google.data'] as $k) {
            Setting::forget($k);
        }
    }

    public function token(): string
    {
        $a = setting('google.oauth_access');
        if (is_array($a) && ($a['expires'] ?? 0) > time()) {
            return $a['token'];
        }
        $r = Http::asForm()->timeout(20)->post('https://oauth2.googleapis.com/token', [
            'client_id' => setting('google.oauth_client_id'), 'client_secret' => setting('google.oauth_client_secret'),
            'refresh_token' => setting('google.oauth_refresh_token'), 'grant_type' => 'refresh_token',
        ]);
        if (! $r->successful()) {
            if ($r->json('error') === 'invalid_grant') {
                Setting::put('google.oauth_error', 'invalid_grant');
            }
            throw new GoogleError('expirat', 'Autorizarea Google a expirat sau a fost revocată.');
        }
        Setting::put('google.oauth_access', ['token' => $r->json('access_token'), 'expires' => time() + (int) $r->json('expires_in') - 60], true);

        return $r->json('access_token');
    }

    public function call(string $method, string $url, array $body = [], array $headers = []): array
    {
        $req = Http::withToken($this->token())->withHeaders($headers)->timeout(25)->acceptJson();
        $r = match ($method) {
            'GET' => $req->get($url, $body),
            'PUT' => $req->put($url, $body),
            default => $req->post($url, $body),
        };
        if ($r->status() === 401) {
            throw new GoogleError('expirat', 'Google a respins tokenul de acces.');
        }
        if ($r->status() === 403) {
            throw new GoogleError('acces', $r->json('error.message') ?? 'Acces necesar: contul nu are permisiune sau API-ul nu este activat în Google Cloud.');
        }
        if (! $r->successful()) {
            throw new GoogleError('eroare', 'Google API: HTTP '.$r->status().' '.mb_substr((string) ($r->json('error.message') ?? $r->body()), 0, 300));
        }

        return $r->json() ?? [];
    }
}
