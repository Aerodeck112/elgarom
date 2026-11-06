@php $items = \App\Models\Industry::published()->get(); @endphp
<section class="sec {{ $tone ?? 'sec--white' }}">
    <div class="wrap">
        @include('sections._head')
        @if ($items->isNotEmpty())
            <div class="tiles">
                @foreach ($items as $i)
                    <div class="tile">
                        @if ($i->image_id)<x-img :media="$i->image_id" sizes="(max-width: 700px) 100vw, 33vw" />@endif
                        <h3>{{ $i->title }}</h3>
                        @if ($i->description)<p>{{ $i->description }}</p>@endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="lead" style="max-width:60ch">Acoperim piese de serie individuale, mici și mari, pentru industrii din domenii diverse. Spuneți-ne ce piesă aveți și vă confirmăm procesul potrivit.</p>
        @endif
    </div>
</section>
