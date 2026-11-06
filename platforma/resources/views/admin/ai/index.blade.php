@extends('admin.layout')
@section('title', 'Asistent AI')
@section('content')
<div class="page-title"><div><h1>Asistent AI</h1><p>Asistentul răspunde pe baza <a href="{{ route('admin.res.index', 'knowledge') }}">bazei de cunoștințe</a>, a serviciilor, întrebărilor frecvente și capacităților confirmate. Cheia API rămâne pe server.</p></div>
<span class="status {{ $status ? 'status--ok' : 'status--warn' }}">{{ $status ? 'Activ pe site' : 'Ascuns pe site' }}</span></div>
<dl class="grid" style="margin:0 0 1.5rem">
    <div class="stat"><dt>Cost azi</dt><dd>${{ number_format($today, 2) }}</dd></div>
    <div class="stat"><dt>Cost luna aceasta</dt><dd>${{ number_format($month, 2) }}</dd></div>
    <div class="stat"><dt>Buget zilnic / lunar</dt><dd>${{ setting('ai.daily_budget_usd', 2) }} / ${{ setting('ai.monthly_budget_usd', 30) }}</dd></div>
    <div class="stat"><dt>Cereri de ofertă prin asistent (luna)</dt><dd>{{ $led }}</dd></div>
</dl>
<form class="panel" method="post" action="{{ route('admin.ai.update') }}">@csrf @method('PUT')
    <h2>Setări</h2>
    <input type="hidden" name="enabled" value="0"><label class="check" style="margin-bottom:1rem"><input type="checkbox" name="enabled" value="1" @checked(setting('ai.enabled'))> <span>Afișează asistentul pe site (doar dacă cheia este configurată și bugetul nu e depășit)</span></label>
    <div class="field"><label for="api_key">Cheie API Anthropic</label><input class="input" type="password" id="api_key" name="api_key" autocomplete="off" placeholder="{{ \App\Services\Ai\Assistant::configured() ? 'Setată – lăsați gol pentru a o păstra' : 'sk-ant-…' }}">
        @if (\App\Services\Ai\Assistant::configured())<label class="check small"><input type="checkbox" name="clear_key" value="1"> <span>Șterge cheia</span></label>@endif
        <p class="hint">Se obține din console.anthropic.com → API Keys. Stocată criptat; nu este afișată niciodată.</p></div>
    <div class="grid-2">
        <div class="field"><label for="model">Model</label><select class="select" id="model" name="model">@foreach ($models as $k => $m)<option value="{{ $k }}" @selected(\App\Services\Ai\Assistant::model() === $k)>{{ $m['label'] }} – ${{ $m['in'] }} / ${{ $m['out'] }} per 1M tokeni</option>@endforeach</select></div>
        <div class="field"><label for="effort">Efort de raționament</label><select class="select" id="effort" name="effort">@foreach (['low' => 'Scăzut (rapid, ieftin – recomandat pentru chat)', 'medium' => 'Mediu', 'high' => 'Ridicat'] as $k => $l)<option value="{{ $k }}" @selected(setting('ai.effort', 'low') === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="field"><label for="mot">Tokeni maximi per răspuns</label><input class="input" type="number" id="mot" name="max_output_tokens" min="1024" max="16000" value="{{ setting('ai.max_output_tokens', 4000) }}"></div>
        <div class="field"><label for="mms">Mesaje maxime per conversație</label><input class="input" type="number" id="mms" name="max_messages_per_session" min="1" max="100" value="{{ setting('ai.max_messages_per_session', 20) }}"></div>
        <div class="field"><label for="mri">Mesaje maxime per IP pe oră</label><input class="input" type="number" id="mri" name="max_requests_per_ip_hour" min="1" max="500" value="{{ setting('ai.max_requests_per_ip_hour', 30) }}"></div>
        <div class="field"><label for="ret">Păstrare conversații (zile)</label><input class="input" type="number" id="ret" name="retention_days" min="1" max="365" value="{{ setting('ai.retention_days', 30) }}"></div>
        <div class="field"><label for="db">Buget zilnic (USD)</label><input class="input" type="number" step="0.01" id="db" name="daily_budget_usd" value="{{ setting('ai.daily_budget_usd', 2) }}"></div>
        <div class="field"><label for="mb">Buget lunar (USD)</label><input class="input" type="number" step="0.01" id="mb" name="monthly_budget_usd" value="{{ setting('ai.monthly_budget_usd', 30) }}"></div>
    </div>
    <p class="hint">La atingerea bugetului, asistentul dispare de pe site până a doua zi / luna următoare; vizitatorii văd formularul și datele de contact. Costul se calculează din tokenii raportați de API la prețurile din config/elgarom.php.</p>
    <div class="btn-row"><button class="btn" type="submit">Salvează</button></div>
</form>
<form method="post" action="{{ route('admin.ai.test') }}" style="margin-bottom:1.5rem">@csrf<button class="btn btn--line" type="submit">Testează conexiunea</button></form>
<div class="panel"><h2>Ultimele 30 de zile</h2>
@if ($days->isEmpty())<p class="muted">Nicio utilizare încă.</p>@else
<div class="table-wrap"><table class="t"><thead><tr><th>Zi</th><th class="num">Cereri</th><th class="num">Refuzate (limite)</th><th class="num">Erori</th><th class="num">Tokeni intrare</th><th class="num">Tokeni ieșire</th><th class="num">Cost</th></tr></thead><tbody>
@foreach ($days as $d)<tr><td>{{ $d->day }}</td><td class="num">{{ $d->requests }}</td><td class="num">{{ $d->refused }}</td><td class="num">{{ $d->errors }}</td><td class="num">{{ number_format($d->tokens_in) }}</td><td class="num">{{ number_format($d->tokens_out) }}</td><td class="num">${{ number_format($d->cost_micro_usd / 1e6, 3) }}</td></tr>@endforeach
</tbody></table></div>@endif
</div>
<div class="panel"><div class="page-title"><h2>Conversații</h2><form method="post" action="{{ route('admin.ai.purge') }}" data-confirm="Ștergeți toate conversațiile?">@csrf<button class="btn btn--danger btn--sm" type="submit">Șterge toate</button></form></div>
<p class="small muted">Conversațiile mai vechi de {{ setting('ai.retention_days', 30) }} de zile se șterg automat (cron zilnic). Vizitatorii își pot șterge singuri conversația.</p>
<div class="table-wrap"><table class="t"><thead><tr><th>Început</th><th>Ultima activitate</th><th class="num">Mesaje</th><th class="num">Cost</th><th>Ofertă</th></tr></thead><tbody>
@forelse ($conversations as $c)<tr><td><a href="{{ route('admin.ai.show', $c) }}">{{ $c->created_at->format('d.m.Y H:i') }}</a></td><td class="small">{{ $c->last_activity_at?->diffForHumans() }}</td><td class="num">{{ $c->messages_count }}</td><td class="num">${{ number_format($c->cost_micro_usd / 1e6, 3) }}</td><td>@if ($c->led_to_quote)<span class="tag tag--ok">da</span>@endif</td></tr>
@empty<tr><td colspan="5" class="muted">Nicio conversație.</td></tr>@endforelse
</tbody></table></div>
{{ $conversations->links('admin.partials.pagination') }}
</div>
@endsection
