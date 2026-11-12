@extends('admin.layout')
@section('title', 'Ștanțe și matrițe')
@section('content')
<div class="page-head"><div><p class="eyebrow"><a href="{{ route('admin.calc.index', 'stante') }}">Calculator ștanțe și matrițe</a></p><h1 class="page-title">Proiecte de ștanțe, matrițe și scule</h1></div><a class="btn btn--accent" href="{{ route('admin.tools.create') }}">Proiect nou</a></div>
<div class="table-wrap"><table class="t"><thead><tr><th>Referință</th><th>Denumire</th><th>Tip</th><th>Client</th><th class="num">Linii</th><th class="num">Cost total</th><th>Termen</th></tr></thead><tbody>
@forelse ($rows as $t) @php $tot = $t->totals(); @endphp
    <tr><td class="mono"><a class="link" href="{{ route('admin.tools.show', $t) }}">{{ $t->reference }}</a></td><td>{{ $t->name }}</td><td>{{ $t->typeLabel() }}</td><td>{{ $t->company?->name ?? '—' }}</td><td class="num">{{ $t->lines->count() }}</td>
        <td class="num">@if (! can('costs.view'))—@elseif ($tot['total'] === null)<span class="pending">{{ $tot['missing'] }} linii fără tarif</span>@else{{ money($tot['total']) }} {{ $t->currency }}@endif</td><td>{{ $t->lead_time ?: '—' }}</td></tr>
@empty <tr><td colspan="7"><div class="empty"><strong>Niciun proiect</strong></div></td></tr>
@endforelse</tbody></table></div>
@include('admin.partials.pagination', ['paginator' => $rows])
@endsection
