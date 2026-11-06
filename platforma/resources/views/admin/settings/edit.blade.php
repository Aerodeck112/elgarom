@extends('admin.layout')
@section('title', $g['label'])
@section('content')
<div class="page-title"><h1>{{ $g['label'] }}</h1></div>
@if ($g['key'] === 'smtp')
    @php $configured = \App\Services\Mail\Outbox::configured(); @endphp
    <div class="panel {{ $configured ? 'panel--ok' : 'panel--warn' }}">
        <p class="status {{ $configured ? 'status--ok' : 'status--warn' }}">{{ $configured ? 'SMTP configurat' : 'SMTP neconfigurat – cererile se salvează, dar emailurile rămân în așteptare' }}</p>
        <p class="small muted">Emailurile se trimit imediat după fiecare cerere. Eșecurile se reîncearcă automat (cron: <code>* * * * * php {{ base_path('artisan') }} schedule:run</code>; fără cron, la vizitele în administrare). Ultima procesare: {{ $lastRun ? \Carbon\Carbon::createFromTimestamp($lastRun)->diffForHumans() : 'niciodată' }}.</p>
    </div>
@endif
<form class="panel" method="post" action="{{ route('admin.settings.update', $g['key']) }}">
    @csrf @method('PUT')
    @foreach ($g['fields'] as $f)
        @php $in = $input($f['name']); $val = old($in, setting($f['name'])); @endphp
        @if ($f['type'] === 'secret')
            <div class="field">
                <label for="f-{{ $in }}">{{ $f['label'] }}</label>
                <input class="input" type="password" id="f-{{ $in }}" name="{{ $in }}" autocomplete="new-password" placeholder="{{ \App\Models\Setting::has($f['name']) ? 'Setată – lăsați gol pentru a o păstra' : 'Nesetată' }}">
                @if (\App\Models\Setting::has($f['name']))<label class="check small"><input type="checkbox" name="{{ $in }}__clear" value="1"> <span>Șterge valoarea salvată</span></label>@endif
                <p class="hint">Stocată criptat. Nu este afișată niciodată după salvare.</p>
            </div>
        @elseif ($f['type'] === 'lines')
            <div class="field"><label for="f-{{ $in }}">{{ $f['label'] }}</label><textarea class="textarea" id="f-{{ $in }}" name="{{ $in }}" rows="3">{{ is_array($val) ? implode("\n", $val) : $val }}</textarea>@if (! empty($f['hint']))<p class="hint">{{ $f['hint'] }}</p>@endif</div>
        @else
            @include('admin.fields.field', ['f' => $f, 'name' => $in, 'value' => $val, 'errKey' => $in])
        @endif
    @endforeach
    <button class="btn" type="submit">Salvează</button>
</form>
@if ($g['key'] === 'smtp')
    <form class="panel" method="post" action="{{ route('admin.smtp.test') }}">
        @csrf
        <h2>Test SMTP</h2>
        <div class="filters"><div class="field"><label for="to">Trimite un email de test la</label><input class="input" id="to" name="to" type="email" value="{{ auth()->user()->email }}" required></div><button class="btn btn--line" type="submit">Trimite testul</button></div>
    </form>
    <div class="panel">
        <div class="page-title"><h2>Coada de emailuri</h2>
            <form method="post" action="{{ route('admin.outbox.retry') }}">@csrf<button class="btn btn--line btn--sm" type="submit" @disabled($outbox->isEmpty())>Reîncearcă toate acum</button></form></div>
        @if ($outbox->isEmpty())<p class="muted">Niciun email în așteptare sau eșuat.</p>@else
        <div class="table-wrap"><table class="t"><thead><tr><th>Creat</th><th>Către</th><th>Subiect</th><th>Stare</th><th>Încercări</th><th>Ultima eroare</th><th></th></tr></thead><tbody>
        @foreach ($outbox as $m)
            <tr><td class="small">{{ $m->created_at->format('d.m H:i') }}</td><td class="small">{{ $m->to_email }}</td><td class="small">{{ $m->subject }}</td>
            <td><span class="tag {{ $m->status === 'failed' ? 'tag--err' : 'tag--warn' }}">{{ ['pending' => 'În așteptare', 'failed' => 'Eșuat definitiv', 'sending' => 'Se trimite'][$m->status] ?? $m->status }}</span>@if ($m->next_attempt_at && $m->status === 'pending')<br><span class="small muted">următoarea: {{ $m->next_attempt_at->format('H:i') }}</span>@endif</td>
            <td class="num">{{ $m->attempts }}</td><td class="small">{{ \Illuminate\Support\Str::limit($m->last_error, 120) }}</td>
            <td><form method="post" action="{{ route('admin.outbox.retry', $m->id) }}">@csrf<button class="link-btn small" type="submit">Reîncearcă</button></form></td></tr>
        @endforeach
        </tbody></table></div>@endif
        @if ($sent->isNotEmpty())<h3 style="margin-top:1.5rem">Ultimele trimise</h3><ul class="small">@foreach ($sent as $m)<li>{{ $m->sent_at?->format('d.m.Y H:i') }} – {{ $m->to_email }} – {{ $m->subject }}</li>@endforeach</ul>@endif
    </div>
@endif
@include('admin.partials.media-picker')
@endsection
