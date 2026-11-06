<?php

namespace App\Services\Google;

use App\Models\Setting;

/** Reads reporting data from Google APIs. Results are cached in settings (google.data). */
class GoogleData
{
    public function __construct(private GoogleClient $g) {}

    public function refreshAll(): array
    {
        $out = ['fetched_at' => now()->toIso8601String()];
        foreach (['gsc', 'ga4', 'gbp', 'ads'] as $svc) {
            if (! GoogleClient::has($svc)) {
                $out[$svc] = ['state' => 'acces', 'message' => 'Permisiunea nu a fost acordată la conectare.'];

                continue;
            }
            try {
                $out[$svc] = ['state' => 'ok'] + $this->{$svc}();
            } catch (GoogleError $e) {
                $out[$svc] = ['state' => $e->state, 'message' => $e->getMessage()];
            } catch (\Throwable $e) {
                $out[$svc] = ['state' => 'eroare', 'message' => mb_substr($e->getMessage(), 0, 300)];
            }
        }
        Setting::put('google.data', $out);

        return $out;
    }

    private function gscProperty(): string
    {
        $p = (string) setting('google.gsc_property');
        if ($p === '') {
            throw new GoogleError('configurare', 'Completați proprietatea Search Console (ex. sc-domain:elgarom.ro sau https://elgarom.ro/).');
        }

        return $p;
    }

    public function gsc(): array
    {
        $site = rawurlencode($this->gscProperty());
        $base = 'https://www.googleapis.com/webmasters/v3/sites/'.$site;
        $range = ['startDate' => now()->subDays(30)->toDateString(), 'endDate' => now()->subDays(2)->toDateString()];
        $totals = $this->g->call('POST', $base.'/searchAnalytics/query', $range);
        $queries = $this->g->call('POST', $base.'/searchAnalytics/query', $range + ['dimensions' => ['query'], 'rowLimit' => 10]);
        $pages = $this->g->call('POST', $base.'/searchAnalytics/query', $range + ['dimensions' => ['page'], 'rowLimit' => 10]);
        $sitemaps = $this->g->call('GET', $base.'/sitemaps');
        $t = $totals['rows'][0] ?? [];

        return [
            'range' => $range,
            'clicks' => (int) ($t['clicks'] ?? 0), 'impressions' => (int) ($t['impressions'] ?? 0),
            'ctr' => round(($t['ctr'] ?? 0) * 100, 2), 'position' => round($t['position'] ?? 0, 1),
            'queries' => array_map(fn ($r) => [$r['keys'][0], (int) $r['clicks'], (int) $r['impressions']], $queries['rows'] ?? []),
            'pages' => array_map(fn ($r) => [$r['keys'][0], (int) $r['clicks'], (int) $r['impressions']], $pages['rows'] ?? []),
            'sitemaps' => array_map(fn ($s) => [$s['path'], $s['lastSubmitted'] ?? null, $s['lastDownloaded'] ?? null, (int) ($s['errors'] ?? 0), (int) ($s['warnings'] ?? 0)], $sitemaps['sitemap'] ?? []),
        ];
    }

    public function submitSitemap(): void
    {
        $site = rawurlencode($this->gscProperty());
        $this->g->call('PUT', 'https://www.googleapis.com/webmasters/v3/sites/'.$site.'/sitemaps/'.rawurlencode(url('/sitemap.xml')));
    }

    public function ga4(): array
    {
        $prop = preg_replace('/\D/', '', (string) setting('google.ga4_property_id'));
        if ($prop === '') {
            throw new GoogleError('configurare', 'Completați ID-ul numeric al proprietății GA4 (Admin → Detalii proprietate).');
        }
        $r = $this->g->call('POST', 'https://analyticsdata.googleapis.com/v1beta/properties/'.$prop.':runReport', [
            'dateRanges' => [['startDate' => '28daysAgo', 'endDate' => 'yesterday']],
            'metrics' => [['name' => 'sessions'], ['name' => 'activeUsers'], ['name' => 'keyEvents']],
        ]);
        $ev = $this->g->call('POST', 'https://analyticsdata.googleapis.com/v1beta/properties/'.$prop.':runReport', [
            'dateRanges' => [['startDate' => '28daysAgo', 'endDate' => 'yesterday']],
            'dimensions' => [['name' => 'eventName']], 'metrics' => [['name' => 'eventCount']],
            'dimensionFilter' => ['filter' => ['fieldName' => 'eventName', 'inListFilter' => ['values' => ['generate_lead', 'contact_click', 'cta_click', 'ai_chat_open', 'file_download']]]],
        ]);
        $v = $r['rows'][0]['metricValues'] ?? [];

        return [
            'sessions' => (int) ($v[0]['value'] ?? 0), 'users' => (int) ($v[1]['value'] ?? 0), 'key_events' => (int) ($v[2]['value'] ?? 0),
            'events' => array_map(fn ($row) => [$row['dimensionValues'][0]['value'], (int) $row['metricValues'][0]['value']], $ev['rows'] ?? []),
        ];
    }

    public function gbp(): array
    {
        $accounts = $this->g->call('GET', 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts');
        $out = ['locations' => []];
        foreach (array_slice($accounts['accounts'] ?? [], 0, 3) as $acc) {
            $locs = $this->g->call('GET', 'https://mybusinessbusinessinformation.googleapis.com/v1/'.$acc['name'].'/locations', [
                'readMask' => 'name,title,storefrontAddress,phoneNumbers,websiteUri,categories', 'pageSize' => 20,
            ]);
            foreach ($locs['locations'] ?? [] as $l) {
                $addr = $l['storefrontAddress'] ?? [];
                $out['locations'][] = [
                    'name' => $l['name'], 'title' => $l['title'] ?? '', 'account' => $acc['accountName'] ?? $acc['name'],
                    'address' => trim(implode(', ', $addr['addressLines'] ?? []).', '.($addr['locality'] ?? ''), ', '),
                    'phone' => $l['phoneNumbers']['primaryPhone'] ?? '', 'website' => $l['websiteUri'] ?? '',
                    'category' => $l['categories']['primaryCategory']['displayName'] ?? '',
                ];
            }
        }

        return $out;
    }

    public function ads(): array
    {
        $cid = preg_replace('/\D/', '', (string) setting('google.ads_customer_id'));
        $dev = (string) setting('google.ads_developer_token');
        if ($cid === '' || $dev === '') {
            throw new GoogleError('configurare', 'Raportarea Google Ads necesită ID-ul de client și un developer token aprobat de Google (Ads → Centrul API).');
        }
        $headers = ['developer-token' => $dev];
        if ($login = preg_replace('/\D/', '', (string) setting('google.ads_login_customer_id'))) {
            $headers['login-customer-id'] = $login;
        }
        $r = $this->g->call('POST', 'https://googleads.googleapis.com/v21/customers/'.$cid.'/googleAds:search', [
            'query' => 'SELECT campaign.name, campaign.status, metrics.impressions, metrics.clicks, metrics.cost_micros, metrics.conversions FROM campaign WHERE segments.date DURING LAST_30_DAYS ORDER BY metrics.cost_micros DESC LIMIT 20',
        ], $headers);

        return ['campaigns' => array_map(fn ($row) => [
            $row['campaign']['name'] ?? '', $row['campaign']['status'] ?? '', (int) ($row['metrics']['impressions'] ?? 0),
            (int) ($row['metrics']['clicks'] ?? 0), round(((int) ($row['metrics']['costMicros'] ?? 0)) / 1e6, 2), (float) ($row['metrics']['conversions'] ?? 0),
        ], $r['results'] ?? [])];
    }
}
