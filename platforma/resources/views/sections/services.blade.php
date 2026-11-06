@php $services = \App\Models\Service::published()->get(); @endphp
@if ($services->isNotEmpty())
<section class="sec {{ $tone ?? 'sec--white' }}">
    <div class="wrap">
        @include('sections._head')
        <div class="services">
            @foreach ($services as $s)
                <a class="service" href="{{ url('/servicii/'.$s->slug) }}">
                    <h3>{{ $s->title }}</h3>
                    <p>{{ $s->summary }}</p>
                    <span class="service__go">Detalii<span class="sr-only"> despre {{ $s->title }}</span></span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif
