<section class="sec {{ $tone ?? 'sec--white' }}">
    <div class="wrap">
        @if (! empty($d['heading']))<h2>{{ $d['heading'] }}</h2>@endif
        <div class="prose">{{ $legal ?? false ? legal_md($d['body'] ?? '') : md($d['body'] ?? '') }}</div>
    </div>
</section>
