@extends('admin.layout')
@section('title', 'Pagini')
@section('content')
<div class="page-title"><div><h1>Pagini și secțiuni</h1><p>Modificările se salvează ca ciornă. Pe site apar doar după „Publică”.</p></div><a class="btn" href="{{ route('admin.pages.create') }}">Pagină nouă</a></div>
<div class="table-wrap"><table class="t">
<thead><tr><th>Pagină</th><th>Adresă</th><th>Tip</th><th>Stare</th><th>Publicată</th></tr></thead>
<tbody>
@foreach ($pages as $p)
<tr>
    <td><a href="{{ route('admin.pages.edit', $p) }}"><strong>{{ $p->title }}</strong></a></td>
    <td><a href="{{ $p->url() }}" target="_blank" rel="noopener" class="small">{{ $p->path() }}</a></td>
    <td class="small">{{ \App\Models\Page::TEMPLATES[$p->template] ?? $p->template }}</td>
    <td>@if ($p->status === 'published')<span class="tag tag--ok">Publicată</span>@else<span class="tag">Ciornă</span>@endif @if ($p->has_unpublished_changes)<span class="tag tag--warn">Modificări nepublicate</span>@endif</td>
    <td class="small">{{ $p->published_at?->format('d.m.Y H:i') }}</td>
</tr>
@endforeach
</tbody></table></div>
@endsection
