@php
    $q = \App\Models\Faq::published();
    if (! empty($d['category'])) { $q->where('category', $d['category']); }
    $faqs = $q->get();
@endphp
@if ($faqs->isNotEmpty())
<section class="sec {{ $tone ?? 'sec--white' }}">
    <div class="wrap">
        @if (! empty($d['heading']))<h2>{{ $d['heading'] }}</h2>@endif
        <div class="faq">
            @foreach ($faqs as $f)
                <details @if ($loop->first) open @endif>
                    <summary>{{ $f->question }}</summary>
                    <div class="faq__a">{{ md($f->answer) }}</div>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif
