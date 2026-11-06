@extends('admin.layout')
@section('title', 'Securitate')
@section('content')
<h1>Securitate</h1>
<div class="panel"><h2>Verificări</h2>
<div class="table-wrap"><table class="t"><tbody>
@foreach ($checks as [$label, $ok, $detail])<tr><td><span class="status {{ $ok ? 'status--ok' : 'status--err' }}">{{ $label }}</span></td><td class="small">{{ $detail }}</td></tr>@endforeach
</tbody></table></div>
<p class="small muted" style="margin-top:1rem">Măsuri active: parole hash-uite (bcrypt), 2FA TOTP obligatoriu cu protecție la reutilizare, coduri de recuperare de unică folosință, limitarea încercărilor, expirare după inactivitate, confirmare cu parolă și cod nou pentru operațiuni sensibile, CSRF, CSP cu nonce, antete de securitate, fișiere încărcate private și neexecutabile, jurnal de audit.</p>
</div>
<form class="panel" method="post" action="{{ route('admin.security.update') }}">@csrf
    <h2>Setări</h2>
    <div class="filters"><div class="field"><label for="idle">Deconectare după inactivitate (minute)</label><input class="input" id="idle" name="idle_minutes" type="number" min="5" max="240" value="{{ setting('security.idle_minutes', 30) }}"></div><button class="btn" type="submit">Salvează</button></div>
</form>
<div class="panel"><h2>Sesiuni active în administrare</h2>
<div class="table-wrap"><table class="t"><thead><tr><th>Utilizator</th><th>Ultima activitate</th><th>IP</th><th>Browser</th><th></th></tr></thead><tbody>
@foreach ($sessions as $s)
<tr><td>{{ $users[$s->user_id]->name ?? '#'.$s->user_id }}</td><td class="small">{{ \Carbon\Carbon::createFromTimestamp($s->last_activity)->diffForHumans() }}</td><td class="small">{{ $s->ip_address }}</td><td class="small">{{ \Illuminate\Support\Str::limit($s->user_agent, 70) }}</td>
<td>@if ($s->id !== session()->getId())<form method="post" action="{{ route('admin.security.revoke', $s->id) }}">@csrf<button class="link-btn link-btn--danger small" type="submit">Închide</button></form>@else<span class="tag tag--info">curentă</span>@endif</td></tr>
@endforeach
</tbody></table></div></div>
<div class="panel"><h2>Încercări eșuate recente</h2>
@if ($failures->isEmpty())<p class="muted">Niciuna.</p>@else<ul class="small">@foreach ($failures as $f)<li>{{ $f->created_at->format('d.m.Y H:i') }} – {{ $f->action }} – {{ $f->ip }} {{ $f->data['email'] ?? '' }}</li>@endforeach</ul>@endif
</div>
@endsection
