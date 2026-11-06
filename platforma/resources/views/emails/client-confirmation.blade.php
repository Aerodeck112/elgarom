@component('emails._layout', ['title' => $q->reference])
<h1 style="font-size:20px;margin:0 0 16px;color:#28387b">Am primit {{ $q->kind === 'contact' ? 'mesajul' : 'cererea de ofertă' }}</h1>
<p>Bună ziua, {{ $q->name }},</p>
<p>Vă mulțumim. {{ $q->kind === 'contact' ? 'Mesajul a fost înregistrat' : 'Cererea a fost înregistrată' }} cu numărul <strong>{{ $q->reference }}</strong>. Echipa Elgarom analizează informațiile și vă contactează.</p>
@if ($q->kind !== 'contact')
<p style="color:#5c666e;font-size:13px">Rezumat: {{ \Illuminate\Support\Str::limit($q->description, 300) }}@if ($q->files->isNotEmpty()) – {{ $q->files->count() }} fișier(e) atașat(e).@endif</p>
@endif
<p>Pentru detalii suplimentare, răspundeți la acest email sau sunați-ne @foreach (setting('company.phones', []) as $p) – {{ $p['label'] }}: {{ $p['number'] }}@endforeach.</p>
<p style="color:#5c666e;font-size:12px">Ați primit acest email pentru că ați trimis un formular pe {{ parse_url(config('app.url'), PHP_URL_HOST) }}. Datele sunt prelucrate conform Politicii de confidențialitate: {{ url('/politica-de-confidentialitate') }}</p>
@endcomponent
