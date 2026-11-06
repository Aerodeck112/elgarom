<section class="sec sec--dark on-dark">
    <div class="wrap group">
        <div>
            @if (! empty($d['heading']))<h2>{{ $d['heading'] }}</h2>@endif
            <div class="prose" style="color:var(--zinc)">{{ md($d['body'] ?? '') }}</div>
        </div>
        @php $companies = array_values(array_filter($d['companies'] ?? [], fn ($c) => ! empty($c['name']))); @endphp
        @if ($companies)
            <ul class="group__list">
                <li><span class="group__self">{{ setting('company.name', 'ELGAROM') }} <small>Zincare electrolitică, Târgu Mureș</small></span></li>
                @foreach ($companies as $c)
                    <li>
                        @if (! empty($c['url']))
                            <a href="{{ safe_url($c['url']) }}" target="_blank" rel="noopener">{{ $c['name'] }} <small>{{ $c['text'] ?: parse_url($c['url'], PHP_URL_HOST) }}<span class="sr-only"> (se deschide într-o filă nouă)</span></small></a>
                        @else
                            <span>{{ $c['name'] }} <small>{{ $c['text'] }}</small></span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
