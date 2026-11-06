@extends('admin.layout')
@section('title', $r['label'])
@section('content')
<div class="page-title">
    <div><p><a href="{{ route('admin.res.index', $r['key']) }}">{{ $r['label'] }}</a></p><h1>{{ $item->exists ? 'Editare '.$r['singular'] : 'Adaugă '.$r['singular'] }}</h1></div>
    @if ($item->exists && ! empty($r['public']) && ($item->is_published ?? false))<a class="btn btn--line" href="{{ ($r['public'])($item) }}" target="_blank" rel="noopener">Vezi pe site</a>@endif
</div>
@if (! empty($r['intro']))<p class="muted">{{ $r['intro'] }}</p>@endif
<form class="panel" method="post" enctype="multipart/form-data" action="{{ $item->exists ? route('admin.res.update', [$r['key'], $item->id]) : route('admin.res.store', $r['key']) }}">
    @csrf
    @if ($item->exists) @method('PUT') @endif
    @foreach ($r['fields'] as $f)
        @include('admin.fields.field', ['f' => $f, 'name' => $f['name'], 'value' => old($f['name'], $item->{$f['name']})])
    @endforeach
    <div class="btn-row"><button class="btn" type="submit">Salvează</button><a class="btn btn--line" href="{{ route('admin.res.index', $r['key']) }}">Înapoi la listă</a></div>
</form>
@if ($item->exists)
    <form method="post" action="{{ route('admin.res.destroy', [$r['key'], $item->id]) }}" data-confirm="Ștergeți definitiv acest element?">
        @csrf @method('DELETE')
        <button class="btn btn--danger btn--sm" type="submit">Șterge</button>
    </form>
@endif
@include('admin.partials.media-picker')
@endsection
