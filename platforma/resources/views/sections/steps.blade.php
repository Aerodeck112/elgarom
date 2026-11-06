@php $items = array_values(array_filter($d['items'] ?? [], fn ($i) => ! empty($i['title']))); @endphp
@if ($items)
<section class="sec {{ $tone ?? 'sec--white' }}" id="etape">
    <div class="wrap">
        @include('sections._head')
        <ol class="steps">
            @foreach ($items as $item)
                <li class="step"><div><h3>{{ $item['title'] }}</h3>@if (! empty($item['text']))<p>{{ $item['text'] }}</p>@endif</div></li>
            @endforeach
        </ol>
        @if (! empty($d['note']))<p class="steps__note">{{ $d['note'] }}</p>@endif
    </div>
</section>
@endif
