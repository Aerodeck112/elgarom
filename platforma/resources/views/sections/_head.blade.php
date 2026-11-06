@if (! empty($d['heading']) || ! empty($d['intro']))
    <div class="sec__head">
        @if (! empty($d['heading']))<h2>{{ $d['heading'] }}</h2>@endif
        @if (! empty($d['intro']))<p>{{ $d['intro'] }}</p>@endif
    </div>
@endif
