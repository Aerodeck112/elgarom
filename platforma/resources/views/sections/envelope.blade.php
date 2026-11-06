@php
    $env = \App\Models\Capability::confirmed()->where('key', 'rack_envelope')->first();
    $barrel = \App\Models\Capability::confirmed()->where('key', 'barrel_load')->first();
    preg_match_all('/\d+(?:[.,]\d+)?/', (string) $env?->value, $mm);
    $dims = array_map(fn ($v) => (float) str_replace(',', '.', $v), $mm[0] ?? []);
@endphp
@if (count($dims) === 3)
@php
    [$a, $b, $c] = $dims;
    $s = 560 / max($a, 1); // drawing scale
    $fw = $a * $s; $fh = min($b * $s, 340); $sw = max(min($c * $s, 200), 40);
    $x0 = 70; $y0 = 92; $sx = $x0 + $fw + 70;
    $fmt = fn ($v) => rtrim(rtrim(number_format($v, 1, ',', ''), '0'), ',');
@endphp
<section class="sec {{ $tone ?? 'sec--white' }}" id="gabarit">
    <div class="wrap">
        @include('sections._head')
        <div class="envelope">
            <figure class="envelope__drawing" style="margin:0">
                <svg viewBox="0 0 {{ (int) ($sx + $sw + 40) }} {{ (int) ($y0 + $fh + 90) }}" role="img" aria-labelledby="env-t env-d">
                    <title id="env-t">Gabarit maxim pe dispozitiv</title>
                    <desc id="env-d">Desen schematic: gabaritul maxim al unei piese pe dispozitiv este {{ $env->value }} {{ $env->unit }}.</desc>
                    <defs>
                        <marker id="tick" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="9" markerHeight="9" orient="auto"><path d="M1 9 L9 1" stroke="#0b7a69" stroke-width="1.6"/></marker>
                    </defs>
                    <g font-family="Archivo Variable, Archivo, sans-serif" font-size="15" fill="#5c666e">
                        {{-- carrier bar and hooks --}}
                        <rect x="{{ $x0 - 30 }}" y="34" width="{{ $fw + 60 }}" height="10" fill="#28387b"/>
                        @foreach ([0.18, 0.5, 0.82] as $h)
                            <path d="M{{ $x0 + $fw * $h }} 44 V{{ $y0 - 8 }} q0 8 -8 8" fill="none" stroke="#28387b" stroke-width="2.5"/>
                        @endforeach
                        {{-- front view envelope --}}
                        <rect x="{{ $x0 }}" y="{{ $y0 }}" width="{{ $fw }}" height="{{ $fh }}" fill="rgba(169,188,203,.16)" stroke="#28387b" stroke-width="2" stroke-dasharray="10 6"/>
                        {{-- example part: perforated L bracket --}}
                        <path d="M{{ $x0 + $fw * .22 }} {{ $y0 + $fh * .18 }} v{{ $fh * .62 }} h{{ $fw * .56 }} v-{{ $fh * .14 }} h-{{ $fw * .42 }} v-{{ $fh * .48 }} z" fill="#d5dade" stroke="#5c666e" stroke-width="1.5"/>
                        @foreach ([0.3, 0.45, 0.6, 0.72] as $hx)
                            <circle cx="{{ $x0 + $fw * $hx }}" cy="{{ $y0 + $fh * .73 }}" r="{{ max(4, $fh * .022) }}" fill="#fff" stroke="#5c666e"/>
                        @endforeach
                        <text x="{{ $x0 }}" y="{{ $y0 - 14 }}" font-size="13">Vedere din față</text>
                        {{-- side view --}}
                        <rect x="{{ $sx }}" y="{{ $y0 }}" width="{{ $sw }}" height="{{ $fh }}" fill="rgba(169,188,203,.16)" stroke="#28387b" stroke-width="2" stroke-dasharray="10 6"/>
                        <text x="{{ $sx }}" y="{{ $y0 - 14 }}" font-size="13">Vedere laterală</text>
                        {{-- dimension lines --}}
                        <g stroke="#0b7a69" stroke-width="1.3" fill="none">
                            <path d="M{{ $x0 }} {{ $y0 + $fh + 18 }} v26 M{{ $x0 + $fw }} {{ $y0 + $fh + 18 }} v26"/>
                            <path d="M{{ $x0 }} {{ $y0 + $fh + 36 }} H{{ $x0 + $fw }}" marker-start="url(#tick)" marker-end="url(#tick)"/>
                            <path d="M{{ $x0 - 18 }} {{ $y0 }} h-26 M{{ $x0 - 18 }} {{ $y0 + $fh }} h-26"/>
                            <path d="M{{ $x0 - 36 }} {{ $y0 }} V{{ $y0 + $fh }}" marker-start="url(#tick)" marker-end="url(#tick)"/>
                            <path d="M{{ $sx }} {{ $y0 + $fh + 18 }} v26 M{{ $sx + $sw }} {{ $y0 + $fh + 18 }} v26"/>
                            <path d="M{{ $sx }} {{ $y0 + $fh + 36 }} H{{ $sx + $sw }}" marker-start="url(#tick)" marker-end="url(#tick)"/>
                        </g>
                        <g fill="#0b7a69" font-weight="640" font-size="19" style="font-stretch:75%">
                            <text x="{{ $x0 + $fw / 2 }}" y="{{ $y0 + $fh + 30 }}" text-anchor="middle" paint-order="stroke" stroke="#fff" stroke-width="6">{{ $fmt($a) }}</text>
                            <text transform="translate({{ $x0 - 44 }} {{ $y0 + $fh / 2 }}) rotate(-90)" text-anchor="middle" paint-order="stroke" stroke="#fff" stroke-width="6">{{ $fmt($b) }}</text>
                            <text x="{{ $sx + $sw / 2 }}" y="{{ $y0 + $fh + 30 }}" text-anchor="middle" paint-order="stroke" stroke="#fff" stroke-width="6">{{ $fmt($c) }}</text>
                        </g>
                        <text x="{{ $sx + $sw + 30 }}" y="{{ $y0 + $fh + 72 }}" text-anchor="end" font-size="13">cote în {{ $env->unit ?: 'mm' }}</text>
                    </g>
                </svg>
                <figcaption class="envelope__src">Desen schematic, nu la scară reală. Piesa din desen este un exemplu. Orientarea pe dispozitiv și prinderea se stabilesc cu echipa tehnică.</figcaption>
            </figure>
            <div class="envelope__data">
                <dl>
                    <div class="datum"><dt>{{ $env->label }}</dt><dd><span class="cota">{{ $env->value }}</span><small>{{ $env->unit }}</small></dd></div>
                    @if ($barrel)
                        <div class="datum"><dt>{{ $barrel->label }}</dt><dd><span class="cota">{{ $barrel->value }}</span><small>{{ $barrel->unit }}</small></dd></div>
                    @endif
                </dl>
                <p class="envelope__src">Sursa: {{ $env->source }}.</p>
                <div class="btn-row" style="margin-top:1.5rem">
                    <a class="btn" href="{{ url('/solicita-oferta') }}" data-track="cta_click" data-track-label="gabarit_oferta">Verificați piesa dvs.</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endif
