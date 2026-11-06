@extends('admin.layout')
@section('title', 'Panou principal')
@section('content')
<div class="page-title"><div><h1>Bună ziua, {{ auth()->user()->name }}</h1><p>Versiune platformă {{ config('elgarom.version') }}</p></div></div>
<dl class="grid" style="margin:0 0 1.5rem">
    @foreach ($stats as $l => $v)<div class="stat"><dt>{{ $l }}</dt><dd>{{ $v }}</dd></div>@endforeach
</dl>
@if ($unpublished->isNotEmpty())
    <div class="panel panel--warn"><h2>Modificări nepublicate</h2><ul>@foreach ($unpublished as $p)<li><a href="{{ route('admin.pages.edit', $p) }}">{{ $p->title }}</a></li>@endforeach</ul></div>
@endif
<div class="grid">
    @if (auth()->user()->can_('quotes.view'))
    <div class="panel">
        <h2>Ultimele cereri</h2>
        @forelse ($latest as $q)
            <p style="margin:.4rem 0"><a href="{{ route('admin.quotes.show', $q) }}"><strong>{{ $q->reference }}</strong></a> – {{ $q->company ?: $q->name }} <span class="tag {{ $q->status === 'nou' ? 'tag--info' : '' }}">{{ $q->statusLabel() }}</span> <span class="small muted">{{ $q->created_at->diffForHumans() }}</span></p>
        @empty
            <p class="muted">Nicio cerere încă.</p>
        @endforelse
    </div>
    @endif
    <div class="panel">
        <h2>De completat</h2>
        <p class="small muted">Informații lipsă din materialele existente. Nu sunt publicate până nu le completați.</p>
        @if ($todo)<ul>@foreach ($todo as [$l, $href])<li><a href="{{ $href }}">{{ $l }}</a></li>@endforeach</ul>@else<p>Totul este completat.</p>@endif
    </div>
    @if (auth()->user()->isAdmin())
    <div class="panel">
        <h2>Stare sistem</h2>
        <dl class="kv">
            <dt>Ultimul backup</dt><dd>@if ($lastBackup){{ $lastBackup->created_at->format('d.m.Y H:i') }} @if ($lastBackup->verified_at)<span class="tag tag--ok">verificat</span>@endif @else<span class="tag tag--warn">niciunul</span>@endif</dd>
            <dt>Cron</dt><dd>@if ($cronOk)<span class="status status--ok">activ</span>@else<span class="status status--warn">nedetectat</span> <span class="small muted">– recomandat pentru reîncercarea emailurilor și curățări</span>@endif</dd>
            <dt>PHP</dt><dd>{{ PHP_VERSION }}</dd>
            <dt>Mod depanare</dt><dd>@if (config('app.debug'))<span class="tag tag--err">ACTIV – dezactivați în producție</span>@else<span class="tag tag--ok">dezactivat</span>@endif</dd>
        </dl>
    </div>
    @endif
</div>
@endsection
