@extends('admin.layout')
@section('title', 'Google și marketing')
@section('content')
@php
    [$stateKey, $stateLabel, $stateHelp] = $state;
    $cls = ['conectat' => 'status--ok', 'neconfigurat' => '', 'neconectat' => 'status--warn', 'expirat' => 'status--err'][$stateKey] ?? '';
    $svcState = function ($key) use ($data) {
        $s = $data[$key]['state'] ?? null;
        return match ($s) { 'ok' => ['status--ok', 'Conectat'], 'acces' => ['status--warn', 'Acces necesar'], 'expirat' => ['status--err', 'Autorizare expirată'], 'configurare' => ['status--warn', 'Configurare necesară'], 'eroare' => ['status--err', 'Eroare'], default => ['', 'Neinterogat'] };
    };
@endphp
<div class="page-title"><div><h1>Google și marketing</h1><p>1) identificatori pentru etichetele din site, 2) conectare OAuth pentru date, 3) date citite prin API, 4) pași care se fac în conturile Google.</p></div></div>
@foreach ($warnings as $w)<div class="alert alert--warn">{{ $w }}</div>@endforeach

<form class="panel" method="post" action="{{ route('admin.google.update') }}">@csrf @method('PUT')
    <h2>1. Identificatori și etichete pe site</h2>
    <p class="small muted">Se acceptă doar identificatori (validați după format), niciodată cod sau scripturi. Etichetele se încarcă doar după acordul vizitatorului, conform setărilor din <a href="{{ route('admin.settings.edit', 'cookies') }}">Cookies și consimțământ</a>.</p>
    <div class="field"><label for="mode">Mod de încărcare</label><select class="select" id="mode" name="mode">
        <option value="direct" @selected(setting('google.mode', 'direct') === 'direct')>Direct: GA4 și conversia Google Ads prin gtag.js</option>
        <option value="gtm" @selected(setting('google.mode') === 'gtm')>Google Tag Manager: doar containerul GTM (GA4/Ads configurate în GTM)</option>
    </select><p class="hint">Un singur mod este activ, ca evenimentele să nu fie numărate de două ori.</p></div>
    <div class="grid-2">
        <div class="field"><label for="gtm_id">Google Tag Manager – ID container</label><input class="input" id="gtm_id" name="gtm_id" placeholder="GTM-XXXXXXX" value="{{ old('gtm_id', setting('google.gtm_id')) }}"></div>
        <div class="field"><label for="ga4_id">Google Analytics 4 – ID de măsurare</label><input class="input" id="ga4_id" name="ga4_id" placeholder="G-XXXXXXXXXX" value="{{ old('ga4_id', setting('google.ga4_id')) }}"></div>
        <div class="field"><label for="ads_id">Google Ads – ID conversie</label><input class="input" id="ads_id" name="ads_id" placeholder="AW-123456789" value="{{ old('ads_id', setting('google.ads_id')) }}"></div>
        <div class="field"><label for="ads_lead_label">Google Ads – etichetă conversie „cerere ofertă”</label><input class="input" id="ads_lead_label" name="ads_lead_label" value="{{ old('ads_lead_label', setting('google.ads_lead_label')) }}"></div>
        <div class="field"><label for="gsc_verification">Search Console – cod de verificare (meta)</label><input class="input" id="gsc_verification" name="gsc_verification" value="{{ old('gsc_verification', setting('google.gsc_verification')) }}"><p class="hint">Doar valoarea „content” din &lt;meta name="google-site-verification" content="…"&gt;.</p></div>
        <div class="field"><label for="gbp_url">Google Business Profile – link public</label><input class="input" id="gbp_url" name="gbp_url" value="{{ old('gbp_url', setting('google.gbp_url')) }}" placeholder="https://maps.app.goo.gl/…"></div>
    </div>
    <h2 style="margin-top:1rem">2. Conectare OAuth (pentru citirea datelor)</h2>
    <div class="grid-2">
        <div class="field"><label for="oauth_client_id">Client OAuth – ID</label><input class="input" id="oauth_client_id" name="oauth_client_id" value="{{ old('oauth_client_id', setting('google.oauth_client_id')) }}"></div>
        <div class="field"><label for="oauth_client_secret">Client OAuth – secret</label><input class="input" type="password" id="oauth_client_secret" name="oauth_client_secret" autocomplete="off" placeholder="{{ \App\Models\Setting::has('google.oauth_client_secret') ? 'Setat – lăsați gol' : '' }}"></div>
        <div class="field"><label for="gsc_property">Proprietate Search Console</label><input class="input" id="gsc_property" name="gsc_property" placeholder="sc-domain:elgarom.ro" value="{{ old('gsc_property', setting('google.gsc_property')) }}"></div>
        <div class="field"><label for="ga4_property_id">GA4 – ID numeric proprietate</label><input class="input" id="ga4_property_id" name="ga4_property_id" value="{{ old('ga4_property_id', setting('google.ga4_property_id')) }}"></div>
        <div class="field"><label for="ads_customer_id">Google Ads – ID client</label><input class="input" id="ads_customer_id" name="ads_customer_id" placeholder="123-456-7890" value="{{ old('ads_customer_id', setting('google.ads_customer_id')) }}"></div>
        <div class="field"><label for="ads_login_customer_id">Google Ads – ID cont manager (opțional)</label><input class="input" id="ads_login_customer_id" name="ads_login_customer_id" value="{{ old('ads_login_customer_id', setting('google.ads_login_customer_id')) }}"></div>
        <div class="field"><label for="ads_developer_token">Google Ads – developer token</label><input class="input" type="password" id="ads_developer_token" name="ads_developer_token" autocomplete="off" placeholder="{{ \App\Models\Setting::has('google.ads_developer_token') ? 'Setat – lăsați gol' : '' }}"></div>
    </div>
    <p class="hint">URI de redirecționare autorizat, de adăugat în clientul OAuth: <code>{{ \App\Services\Google\GoogleClient::redirectUri() }}</code></p>
    <button class="btn" type="submit">Salvează</button>
</form>

<div class="panel">
    <div class="page-title"><h2>Starea conexiunii</h2><span class="status {{ $cls }}">{{ $stateLabel }}</span></div>
    <p class="small">{{ $stateHelp }}</p>
    @if ($stateKey !== 'neconfigurat')
    <form method="post" action="{{ route('admin.google.connect') }}">@csrf
        <fieldset style="border:0;padding:0;margin:0 0 1rem"><legend class="small">Permisiuni cerute</legend>
        @foreach ($scopes as $k => [$scope, $label])<label class="check"><input type="checkbox" name="services[]" value="{{ $k }}" @checked(in_array($scope, $granted, true) || ! $granted)> <span>{{ $label }}</span></label>@endforeach
        </fieldset>
        <button class="btn" type="submit">{{ $stateKey === 'conectat' ? 'Reconectează' : 'Conectează contul Google' }}</button>
    </form>
    @if ($stateKey !== 'neconectat')
        <div class="btn-row" style="margin-top:1rem">
            <form method="post" action="{{ route('admin.google.disconnect') }}">@csrf<button class="btn btn--line" type="submit">Deconectează</button></form>
            <form method="post" action="{{ route('admin.google.refresh') }}">@csrf<button class="btn btn--line" type="submit">Citește datele acum</button></form>
        </div>
    @endif
    @endif
</div>

<h2>3. Date citite prin API @if (! empty($data['fetched_at']))<span class="small muted">({{ \Carbon\Carbon::parse($data['fetched_at'])->format('d.m.Y H:i') }})</span>@endif</h2>
<div class="grid">
    @php [$c, $l] = $svcState('gsc'); $d = $data['gsc'] ?? []; @endphp
    <div class="panel"><div class="page-title"><h3>Search Console</h3><span class="status {{ $c }}">{{ $l }}</span></div>
        @if (($d['state'] ?? '') === 'ok')
            <dl class="kv"><dt>Clicuri (30 zile)</dt><dd>{{ $d['clicks'] }}</dd><dt>Afișări</dt><dd>{{ $d['impressions'] }}</dd><dt>CTR</dt><dd>{{ $d['ctr'] }}%</dd><dt>Poziție medie</dt><dd>{{ $d['position'] }}</dd></dl>
            @if ($d['queries'])<h3 style="margin-top:1rem">Interogări</h3><ul class="small">@foreach ($d['queries'] as [$q, $cl, $im])<li>{{ $q }} – {{ $cl }} clicuri, {{ $im }} afișări</li>@endforeach</ul>@endif
            <h3 style="margin-top:1rem">Sitemap-uri</h3>@if ($d['sitemaps'])<ul class="small">@foreach ($d['sitemaps'] as [$p, $sub, $dl, $er, $wa])<li>{{ $p }} – trimis {{ $sub ? \Carbon\Carbon::parse($sub)->format('d.m.Y') : '—' }}, erori {{ $er }}, avertismente {{ $wa }}</li>@endforeach</ul>@else<p class="small muted">Niciun sitemap trimis.</p>@endif
        @elseif (! empty($d['message']))<p class="small">{{ $d['message'] }}</p>@endif
        @if (\App\Services\Google\GoogleClient::has('gsc'))<form method="post" action="{{ route('admin.google.sitemap') }}">@csrf<button class="btn btn--line btn--sm" type="submit">Trimite {{ url('/sitemap.xml') }}</button></form>@endif
    </div>
    @php [$c, $l] = $svcState('ga4'); $d = $data['ga4'] ?? []; @endphp
    <div class="panel"><div class="page-title"><h3>Google Analytics 4</h3><span class="status {{ $c }}">{{ $l }}</span></div>
        @if (($d['state'] ?? '') === 'ok')
            <dl class="kv"><dt>Sesiuni (28 zile)</dt><dd>{{ $d['sessions'] }}</dd><dt>Utilizatori activi</dt><dd>{{ $d['users'] }}</dd><dt>Evenimente cheie</dt><dd>{{ $d['key_events'] }}</dd></dl>
            @if ($d['events'])<ul class="small" style="margin-top:.75rem">@foreach ($d['events'] as [$n, $v])<li>{{ $n }}: {{ $v }}</li>@endforeach</ul>@endif
        @elseif (! empty($d['message']))<p class="small">{{ $d['message'] }}</p>@endif
    </div>
    @php [$c, $l] = $svcState('gbp'); $d = $data['gbp'] ?? []; $siteAddr = company_address(); @endphp
    <div class="panel"><div class="page-title"><h3>Business Profile</h3><span class="status {{ $c }}">{{ $l }}</span></div>
        @if (($d['state'] ?? '') === 'ok')
            @forelse ($d['locations'] as $loc)
                <dl class="kv"><dt>Nume</dt><dd>{{ $loc['title'] }}</dd><dt>Adresă</dt><dd>{{ $loc['address'] }}</dd><dt>Telefon</dt><dd>{{ $loc['phone'] }}</dd><dt>Website</dt><dd>{{ $loc['website'] }}</dd><dt>Categorie</dt><dd>{{ $loc['category'] }}</dd></dl>
                <p class="small muted">Comparați cu datele din site: {{ $siteAddr }}. Numele, adresa și telefonul trebuie să coincidă.</p>
            @empty<p class="small">Contul nu are locații.</p>@endforelse
        @elseif (! empty($d['message']))<p class="small">{{ $d['message'] }}</p>@endif
        <p class="small muted">Recenziile și postările necesită acces aprobat de Google la API-urile Business Profile. Până atunci, gestionați-le în <a href="https://business.google.com/" target="_blank" rel="noopener">business.google.com</a>.</p>
    </div>
    @php [$c, $l] = $svcState('ads'); $d = $data['ads'] ?? []; @endphp
    <div class="panel"><div class="page-title"><h3>Google Ads</h3><span class="status {{ $c }}">{{ $l }}</span></div>
        @if (($d['state'] ?? '') === 'ok')
            @if ($d['campaigns'])<div class="table-wrap"><table class="t"><thead><tr><th>Campanie</th><th class="num">Clicuri</th><th class="num">Cost</th><th class="num">Conv.</th></tr></thead><tbody>@foreach ($d['campaigns'] as [$n, $st, $im, $cl, $cost, $conv])<tr><td class="small">{{ $n }}</td><td class="num">{{ $cl }}</td><td class="num">{{ $cost }}</td><td class="num">{{ $conv }}</td></tr>@endforeach</tbody></table></div>@else<p class="small">Nicio campanie activă în ultimele 30 de zile.</p>@endif
        @elseif (! empty($d['message']))<p class="small">{{ $d['message'] }}</p>@endif
        <p class="small muted">Platforma doar citește rapoarte. Bugetele și campaniile se modifică doar în contul Google Ads.</p>
    </div>
</div>

<div class="panel"><h2>Evenimente trimise de site</h2>
<p class="small muted">Fără date personale, mesaje sau fișiere – doar numele evenimentului și o etichetă. În modul GTM, evenimentele ajung în dataLayer; în modul direct, la GA4 prin gtag.</p>
<div class="table-wrap"><table class="t"><thead><tr><th>Eveniment</th><th>Când</th><th>Parametri</th></tr></thead><tbody>
<tr><td><code>generate_lead</code></td><td>Cererea de ofertă sau mesajul a fost salvat (pagina de confirmare, o singură dată per cerere)</td><td>form_type</td></tr>
<tr><td><code>cta_click</code></td><td>Clic pe butoanele „Solicită ofertă” / „Descoperă procesul”</td><td>link_label</td></tr>
<tr><td><code>contact_click</code></td><td>Clic pe telefon sau email</td><td>link_label</td></tr>
<tr><td><code>quote_step</code></td><td>Avans în formularul de ofertă</td><td>step</td></tr>
<tr><td><code>ai_chat_open</code>, <code>ai_prefill_accepted</code></td><td>Deschiderea asistentului / acceptarea precompletării</td><td>—</td></tr>
<tr><td><code>file_download</code>, <code>map_load</code></td><td>Descărcare document / afișare hartă</td><td>link_label</td></tr>
</tbody></table></div>
<p class="small muted" style="margin-top:.75rem">Configurație publică trimisă browserului acum: <code>{{ json_encode(\Illuminate\Support\Arr::except($public, ['ai'])) }}</code></p></div>

<div class="panel"><h2>4. Pași în conturile Google</h2>
<ol class="small">
    <li><strong>Search Console – verificare:</strong> recomandat „Domeniu” (sc-domain:elgarom.ro) cu înregistrare DNS TXT: în cPanel → Zone Editor → Add Record → TXT, nume „elgarom.ro.”, valoare „google-site-verification=…” primită de la Google; propagarea poate dura până la 48 h. Alternativ, proprietate „Prefix URL” cu codul meta de mai sus.</li>
    <li><strong>Client OAuth:</strong> console.cloud.google.com → APIs &amp; Services → activați Search Console API, Google Analytics Data API, My Business Account Management + Business Information API, Google Ads API → Credentials → OAuth client ID (Web) → adăugați URI-ul de redirecționare de mai sus. Pe ecranul de consimțământ adăugați contul care se conectează.</li>
    <li><strong>GA4:</strong> marcați „generate_lead” ca eveniment cheie (Admin → Evenimente).</li>
    <li><strong>Google Ads:</strong> conversia „cerere ofertă” se creează în Ads (Obiective → Conversii); developer token-ul se solicită din Centrul API și necesită aprobare Google.</li>
    <li><strong>Business Profile:</strong> accesul la API se solicită prin formularul Google pentru Business Profile APIs; fără aprobare, datele nu pot fi citite.</li>
</ol></div>
@endsection
