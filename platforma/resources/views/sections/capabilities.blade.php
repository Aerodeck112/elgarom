@php
    $q = \App\Models\Capability::confirmed();
    if (! empty($d['group'])) { $q->where('group', $d['group']); }
    $caps = $q->get();
@endphp
@if ($caps->isNotEmpty())
<section class="sec {{ $tone ?? 'sec--white' }}">
    <div class="wrap">
        @include('sections._head')
        <table class="caps">
            <caption>Sursa datelor: informații publicate de Elgarom. Pentru valori nelistate, contactați echipa tehnică la {{ (setting('company.phones', [])[1]['number'] ?? '') }}.</caption>
            <tbody>
                @foreach ($caps as $c)
                    <tr><th scope="row">{{ $c->label }}</th><td>@if (is_numeric($c->value))<span class="cota">{{ $c->value }}</span> {{ $c->unit }}@else{{ $c->display() }}@endif @if ($c->note)<br><span class="small muted">{{ $c->note }}</span>@endif</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endif
