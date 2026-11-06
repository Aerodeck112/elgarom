@php $items = array_values(array_filter($d['items'] ?? [], fn ($i) => ! empty($i['title']))); @endphp
@if ($items)
<section class="sec {{ $tone ?? 'sec--white' }}">
    <div class="wrap">
        @include('sections._head')
        @php $plain = collect($items)->every(fn ($i) => empty($i['text'])); @endphp
        <div @class(['features', 'features--plain' => $plain])>
            @foreach ($items as $item)
                <div class="feature">
                    <svg class="lmark" viewBox="0 0 22 22" aria-hidden="true"><path d="M3 2v17h17" fill="none" stroke="currentColor" stroke-width="3"/></svg>
                    <h3>{{ $item['title'] }}</h3>
                    @if (! empty($item['text']))<p>{{ $item['text'] }}</p>@endif
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
