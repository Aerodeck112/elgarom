@php
    $q = \App\Models\Document::public();
    if (! empty($d['category'])) { $q->where('category', $d['category']); }
    $docs = $q->get();
@endphp
<section class="sec {{ $tone ?? 'sec--white' }}">
    <div class="wrap">
        @include('sections._head')
        @if ($docs->isNotEmpty())
            <ul class="docs">
                @foreach ($docs as $doc)
                    <li>
                        <div><strong>{{ $doc->title }}</strong><small>{{ $doc->description }}@if ($doc->valid_until) {{ __('Valabil până la :date.', ['date' => $doc->valid_until->format('d.m.Y')]) }}@endif</small></div>
                        <a class="btn btn--sm btn--line" href="{{ $doc->url() }}" data-track="file_download" data-track-label="document">{{ __('Descarcă') }} {{ strtoupper(pathinfo($doc->file_name, PATHINFO_EXTENSION)) }}<span class="sr-only">: {{ $doc->title }}</span></a>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="muted" style="max-width:60ch">{{ __('Pentru documentele de calitate solicitate în procesul de achiziție, scrieți-ne la') }} <a href="mailto:{{ company_email() }}">{{ company_email() }}</a>.</p>
        @endif
    </div>
</section>
