@extends('admin.layout')
@section('title', $res['title'])
@section('content')
<div class="page-head"><div><p class="eyebrow"><a href="{{ route('admin.res.index', $res['key']) }}">{{ $res['title'] }}</a></p><h1 class="page-title">{{ $row->exists ? 'Modifică '.$res['singular'] : 'Adaugă '.$res['singular'] }}</h1></div></div>
<div class="cols">
    <form class="panel form-grid" method="post" action="{{ $row->exists ? route('admin.res.update', [$res['key'], $row->id]) : route('admin.res.store', $res['key']) }}">
        @csrf @if ($row->exists) @method('PUT') @endif
        @foreach ($res['fields'] as $f)
            @include('admin.partials.field', ['f' => $f, 'value' => $row->{$f['name']}])
        @endforeach
        <div class="span-12 btn-row"><button class="btn" type="submit">Salvează</button><a class="btn btn--line" href="{{ route('admin.res.index', $res['key']) }}">Renunță</a></div>
    </form>
    <div class="stack">
        @if (! empty($history) && $history->isNotEmpty())
            <div class="panel"><h2>Istoric tarife</h2><table class="t"><tbody>@foreach ($history as $h)<tr><td class="small mono">{{ $h->created_at?->format('d.m.Y') }}</td><td class="num">{{ money($h->old_value) }} → {{ money($h->new_value) }} {{ $h->currency }}</td></tr>@endforeach</tbody></table></div>
        @endif
        @if ($row->exists)
            <form class="panel" method="post" action="{{ route('admin.res.destroy', [$res['key'], $row->id]) }}" data-confirm="Ștergeți definitiv această înregistrare?">@csrf @method('DELETE')
                <h2>Ștergere</h2><p class="small muted">Înregistrările folosite în oferte sau calcule nu pot fi șterse; dezactivați-le.</p><button class="btn btn--danger btn--sm" type="submit">Șterge</button></form>
        @endif
    </div>
</div>
@endsection
