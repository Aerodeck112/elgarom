@extends('admin.layout')
@section('title', 'Calculator zincare')
@section('content')
<div class="page-head"><div><p class="eyebrow">ELGAROM · zincare electrolitică</p><h1 class="page-title">Calculator zincare electrolitică</h1></div><div class="btn-row"><span class="pending">Formulă în curs de configurare</span><a class="btn btn--accent" href="{{ route('admin.zinc.create') }}">Fișă nouă</a></div></div>
<div class="panel panel--warn" style="margin-bottom:18px"><p style="margin:0">Pentru zincare nu a fost furnizat încă un fișier Excel cu formule și tarife validate. Fișa tehnică se poate completa și salva de pe acum, cu verificarea limitelor confirmate ale liniei (dispozitive: {{ config('simex.zinc_limits.rack.length_mm') }} × {{ config('simex.zinc_limits.rack.width_mm') }} × {{ config('simex.zinc_limits.rack.depth_mm') }} mm; tambur: {{ config('simex.zinc_limits.barrel.load_kg') }} kg). Costul nu este calculat: prețul se introduce manual în ofertă.</p></div>
<div class="table-wrap"><table class="t"><thead><tr><th>Referință</th><th>Titlu</th><th>Client</th><th>Încărcare</th><th class="num">Piese</th><th>Avertizări</th></tr></thead><tbody>
@forelse ($rows as $j)<tr><td class="mono"><a class="link" href="{{ route('admin.zinc.edit', $j) }}">{{ $j->reference }}</a></td><td>{{ $j->title }}</td><td>{{ $j->company?->name ?? '—' }}</td><td>{{ ($j->inputs['loading'] ?? '') === 'tambur' ? 'Tambur' : 'Dispozitiv' }}</td><td class="num">{{ qty($j->inputs['pieces'] ?? null) }}</td>
    <td>@if ($j->warnings())<span class="tag tag--warn">{{ count($j->warnings()) }}</span>@else<span class="tag tag--ok">în limite</span>@endif</td></tr>
@empty <tr><td colspan="6"><div class="empty">Nicio fișă de zincare.</div></td></tr>@endforelse</tbody></table></div>
@include('admin.partials.pagination', ['paginator' => $rows])
@endsection
