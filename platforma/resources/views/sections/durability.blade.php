@php $items = array_values(array_filter($d['items'] ?? [], fn ($i) => ! empty($i['title']))); @endphp
<section class="sec {{ $tone ?? 'sec--zinc' }}" id="durabilitate">
    <div class="wrap dur">
        <div>
            @if (! empty($d['heading']))<h2>{{ $d['heading'] }}</h2>@endif
            @if (! empty($d['intro']))<p class="lead" style="max-width:46ch">{{ $d['intro'] }}</p>@endif
            @if ($items)
                <div class="features features--plain" style="margin-top:2rem">
                    @foreach ($items as $item)
                        <div class="feature"><svg class="lmark" viewBox="0 0 22 22" aria-hidden="true"><path d="M3 2v17h17" fill="none" stroke="currentColor" stroke-width="3"/></svg><h3>{{ $item['title'] }}</h3>@if (! empty($item['text']))<p>{{ $item['text'] }}</p>@endif</div>
                    @endforeach
                </div>
            @endif
        </div>
        <figure class="dur__viz on-dark" style="margin:0" data-durability>
            <svg viewBox="0 0 560 330" role="img" aria-labelledby="dur-t dur-d">
                <title id="dur-t">{{ __('Ilustrație: consumul stratului de zinc în medii diferite') }}</title>
                <desc id="dur-d">{{ __('Secțiune schematică prin piesă: oțel, strat de zinc, pasivare și top coating. Stratul de zinc se consumă mai lent într-un mediu uscat și mai repede într-un mediu umed, salin sau industrial. Ilustrație calitativă, fără valori.') }}</desc>
                <g font-family="Archivo Variable, Archivo, sans-serif" font-size="13">
                    <rect x="30" y="200" width="500" height="90" fill="#5c666e"/>
                    <text x="44" y="272" fill="#eef0f1">{{ __('Oțel') }}</text>
                    <rect data-zinc x="30" y="140" width="500" height="60" fill="#a9bccb"/>
                    <text x="44" y="176" fill="#20262b">{{ __('Strat de zinc') }}</text>
                    <rect data-pass x="30" y="128" width="500" height="12" fill="#14ad95" opacity=".85"/>
                    <rect data-top x="30" y="120" width="500" height="8" fill="#6fd6c4" opacity=".7"/>
                    <text x="520" y="114" text-anchor="end" fill="#d5dade" font-size="12">{{ __('pasivare + top coating') }}</text>
                    <g data-attack fill="#d5dade">
                        @for ($i = 0; $i < 14; $i++)
                            <circle cx="{{ 50 + $i * 35 }}" cy="{{ 40 + ($i % 3) * 18 }}" r="3" opacity=".7"/>
                        @endfor
                    </g>
                    <text x="30" y="22" fill="#9aa3aa" font-size="12">{{ __('Mediu') }}</text>
                    <path d="M30 310 H530" stroke="#3a434a"/>
                    <text x="30" y="326" fill="#9aa3aa" font-size="12">{{ __('Ilustrație calitativă – fără scară de timp') }}</text>
                </g>
            </svg>
            <div class="dur__controls" role="group" aria-label="{{ __('Alegeți mediul de utilizare') }}">
                <button type="button" aria-pressed="true" data-env="0" data-text="{{ __('Interior uscat: coroziunea este lentă, iar stratul se consumă foarte încet.') }}">{{ __('Interior uscat') }}</button>
                <button type="button" aria-pressed="false" data-env="1" data-text="{{ __('Exterior, mediu obișnuit: ploaia și condensul consumă stratul treptat.') }}">{{ __('Exterior') }}</button>
                <button type="button" aria-pressed="false" data-env="2" data-text="{{ __('Mediu industrial sau salin: agenții agresivi accelerează consumul stratului. Grosimea și tratamentele contează cel mai mult aici.') }}">{{ __('Industrial / salin') }}</button>
            </div>
            <p class="dur__caption" aria-live="polite" data-dur-caption>{{ __('Interior uscat: coroziunea este lentă, iar stratul se consumă foarte încet.') }}</p>
        </figure>
    </div>
    @if (! empty($d['note']))<div class="wrap"><p class="dur__note">{{ $d['note'] }}</p></div>@endif
</section>
