<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\Google\GoogleClient;
use App\Services\Google\GoogleData;
use App\Support\TagConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    public function index()
    {
        $warnings = [];
        if (setting('google.mode') === 'direct' && TagConfig::valid('gtm_id', setting('google.gtm_id'))) {
            $warnings[] = 'Modul este „direct”: containerul GTM introdus NU se încarcă. Alegeți un singur mod pentru a evita dublarea evenimentelor.';
        }
        if (setting('google.mode') === 'gtm' && (setting('google.ga4_id') || setting('google.ads_id'))) {
            $warnings[] = 'Modul este „GTM”: ID-urile GA4/Ads de mai jos nu se încarcă direct. Configurați eticheta GA4 și conversia Ads în containerul GTM, cu declanșatorul pe evenimentul „generate_lead”.';
        }
        if (setting('google.mode') === 'gtm' && ! TagConfig::valid('gtm_id', setting('google.gtm_id'))) {
            $warnings[] = 'Modul GTM este ales, dar ID-ul containerului lipsește sau este invalid: nu se încarcă nicio etichetă.';
        }

        return view('admin.google.index', [
            'state' => GoogleClient::state(),
            'data' => setting('google.data', []),
            'public' => TagConfig::public(),
            'warnings' => $warnings,
            'scopes' => GoogleClient::SCOPES,
            'granted' => GoogleClient::granted(),
        ]);
    }

    public function update(Request $request)
    {
        $p = TagConfig::PATTERNS;
        $d = $request->validate([
            'mode' => 'required|in:direct,gtm',
            'gtm_id' => ['nullable', 'regex:'.$p['gtm_id']],
            'ga4_id' => ['nullable', 'regex:'.$p['ga4_id']],
            'ads_id' => ['nullable', 'regex:'.$p['ads_id']],
            'ads_lead_label' => ['nullable', 'regex:'.$p['ads_lead_label']],
            'gsc_verification' => ['nullable', 'regex:'.$p['gsc_verification']],
            'gsc_property' => ['nullable', 'max:200', 'regex:/^(sc-domain:[a-z0-9.\-]+|https:\/\/[a-z0-9.\-]+\/)$/'],
            'ga4_property_id' => ['nullable', 'regex:/^\d{6,12}$/'],
            'gbp_url' => ['nullable', 'url:https', 'max:500'],
            'ads_customer_id' => ['nullable', 'regex:/^\d{3}-?\d{3}-?\d{4}$/'],
            'ads_login_customer_id' => ['nullable', 'regex:/^\d{3}-?\d{3}-?\d{4}$/'],
            'ads_developer_token' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'oauth_client_id' => ['nullable', 'string', 'max:200', 'regex:/^[0-9a-z\-]+\.apps\.googleusercontent\.com$/'],
            'oauth_client_secret' => ['nullable', 'string', 'max:200', 'regex:/^[A-Za-z0-9_\-]+$/'],
        ], [
            'gtm_id.regex' => 'ID GTM invalid (format GTM-XXXXXXX).', 'ga4_id.regex' => 'ID de măsurare GA4 invalid (format G-XXXXXXXXXX).',
            'ads_id.regex' => 'ID Google Ads invalid (format AW-123456789).', 'ads_lead_label.regex' => 'Eticheta de conversie conține caractere nepermise.',
            'gsc_verification.regex' => 'Introduceți doar valoarea atributului content din eticheta meta (fără HTML).',
            'gsc_property.regex' => 'Format: sc-domain:elgarom.ro sau https://elgarom.ro/',
            'oauth_client_id.regex' => 'ID-ul de client OAuth se termină cu .apps.googleusercontent.com',
        ]);
        foreach (['mode', 'gtm_id', 'ga4_id', 'ads_id', 'ads_lead_label', 'gsc_verification', 'gsc_property', 'ga4_property_id', 'gbp_url', 'ads_customer_id', 'ads_login_customer_id', 'oauth_client_id'] as $k) {
            Setting::put('google.'.$k, $d[$k] ?? null);
        }
        foreach (['ads_developer_token', 'oauth_client_secret'] as $k) {
            if (! empty($d[$k])) {
                Setting::put('google.'.$k, $d[$k], true);
            }
        }
        AuditLog::record('google.setari', null, ['mod' => $d['mode']]);

        return back()->with('status', 'Configurarea Google a fost salvată.');
    }

    public function connect(Request $request)
    {
        if (! GoogleClient::configured()) {
            return back()->with('error', 'Introduceți și salvați întâi ID-ul și secretul clientului OAuth.');
        }
        $services = array_intersect((array) $request->input('services', []), array_keys(GoogleClient::SCOPES));
        if (! $services) {
            return back()->with('error', 'Alegeți cel puțin un serviciu.');
        }
        $state = Str::random(40);
        $request->session()->put('google.oauth_state', $state);

        return redirect()->away(GoogleClient::authUrl($services, $state));
    }

    public function callback(Request $request)
    {
        $expected = $request->session()->pull('google.oauth_state');
        if (! $expected || ! hash_equals($expected, (string) $request->query('state'))) {
            return redirect()->route('admin.google.index')->with('error', 'Răspuns OAuth invalid (state). Reîncercați conectarea.');
        }
        if ($err = $request->query('error')) {
            return redirect()->route('admin.google.index')->with('error', 'Conectarea a fost anulată sau refuzată: '.e($err));
        }
        try {
            GoogleClient::exchange((string) $request->query('code'));
        } catch (\Throwable $e) {
            return redirect()->route('admin.google.index')->with('error', $e->getMessage());
        }
        AuditLog::record('google.conectat', null, ['scopes' => GoogleClient::granted()]);

        return redirect()->route('admin.google.index')->with('status', 'Contul Google a fost conectat.');
    }

    public function disconnect()
    {
        GoogleClient::disconnect();
        AuditLog::record('google.deconectat');

        return back()->with('status', 'Contul Google a fost deconectat și autorizarea revocată.');
    }

    public function refresh(GoogleData $data)
    {
        if (! GoogleClient::connected()) {
            return back()->with('error', 'Conectați întâi contul Google.');
        }
        $data->refreshAll();
        AuditLog::record('google.date_actualizate');

        return back()->with('status', 'Datele au fost citite din Google.');
    }

    public function submitSitemap(GoogleData $data)
    {
        try {
            $data->submitSitemap();
        } catch (\Throwable $e) {
            return back()->with('error', 'Trimiterea sitemap-ului a eșuat: '.$e->getMessage());
        }
        AuditLog::record('google.sitemap_trimis');

        return back()->with('status', 'Sitemap-ul '.url('/sitemap.xml').' a fost trimis în Search Console.');
    }
}
