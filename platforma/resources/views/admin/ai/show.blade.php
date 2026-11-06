@extends('admin.layout')
@section('title', 'Conversație AI')
@section('content')
<div class="page-title"><div><p><a href="{{ route('admin.ai.index') }}">Asistent AI</a></p><h1>Conversație din {{ $c->created_at->format('d.m.Y H:i') }}</h1><p>{{ $c->messages_count }} mesaje, cost ${{ number_format($c->cost_micro_usd / 1e6, 3) }}</p></div>
<form method="post" action="{{ route('admin.ai.destroy', $c) }}" data-confirm="Ștergeți conversația?">@csrf @method('DELETE')<button class="btn btn--danger btn--sm" type="submit">Șterge</button></form></div>
<div class="panel">@foreach ($c->messages as $m)<p><strong>{{ $m->role === 'user' ? 'Vizitator' : 'Asistent' }}</strong> <span class="small muted">{{ $m->created_at?->format('H:i') }}@if ($m->status !== 'ok'), {{ $m->status }}@endif</span><br><span class="pre">{{ $m->content }}</span></p>@endforeach</div>
@endsection
