@component('emails._layout', ['title' => $q->reference])
<h1 style="font-size:20px;margin:0 0 16px;color:#28387b">{{ $q->kind === 'contact' ? __('Am primit mesajul') : __('Am primit cererea de ofertă') }}</h1>
<p>{{ __('Bună ziua, :name,', ['name' => $q->name]) }}</p>
<p>{{ __('Vă mulțumim.') }} {{ $q->kind === 'contact' ? __('Mesajul a fost înregistrat cu numărul :ref.', ['ref' => $q->reference]) : __('Cererea a fost înregistrată cu numărul :ref.', ['ref' => $q->reference]) }} {{ __('Echipa Elgarom analizează informațiile și vă contactează.') }}</p>
@if ($q->kind !== 'contact')
<p style="color:#5c666e;font-size:13px">{{ __('Rezumat') }}: {{ \Illuminate\Support\Str::limit($q->description, 300) }}@if ($q->files->isNotEmpty()) – {{ trans_choice('{1} :count fișier atașat.|[2,*] :count fișiere atașate.', $q->files->count()) }}@endif</p>
@endif
<p>{{ __('Pentru detalii suplimentare, răspundeți la acest email sau sunați-ne:') }} @foreach (setting('company.phones', []) as $p){{ $loop->first ? '' : ', ' }}{{ __($p['label']) }} {{ $p['number'] }}@endforeach.</p>
<p style="color:#5c666e;font-size:12px">{{ __('Ați primit acest email pentru că ați trimis un formular pe :site. Datele sunt prelucrate conform Politicii de confidențialitate:', ['site' => parse_url(config('app.url'), PHP_URL_HOST)]) }} {{ page_url('politica-de-confidentialitate') }}</p>
@endcomponent
