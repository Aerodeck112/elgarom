@php $items = \App\Models\Project::published()->get(); @endphp
@if ($items->isNotEmpty())
<section class="sec {{ $tone ?? 'sec--white' }}">
    <div class="wrap">
        @include('sections._head')
        <div class="tiles">
            @foreach ($items as $p)
                <a class="tile" href="{{ url('/proiecte/'.$p->slug) }}">
                    @if ($p->cover_id)<x-img :media="$p->cover_id" sizes="(max-width: 700px) 100vw, 33vw" />@endif
                    <h3>{{ $p->title }}</h3>
                    @if ($p->summary)<p>{{ $p->summary }}</p>@endif
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif
