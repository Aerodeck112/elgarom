@extends('admin.layout')
@section('title', 'Backup și restaurare')
@section('content')
<div class="page-title"><div><h1>Backup și restaurare</h1><p>Backup-ul complet conține baza de date, imaginile și fișierele private (cereri, documente). Arhivele sunt stocate în afara directorului public.</p></div>
<form method="post" action="{{ route('admin.backups.store') }}">@csrf<button class="btn" type="submit">Creează backup acum</button></form></div>
<p class="small muted">Spațiu liber pe disc: {{ $free ? number_format($free / 1073741824, 1, ',', '').' GB' : 'necunoscut' }}. Backup automat: zilnic prin cron (<code>schedule:run</code>), se păstrează ultimele {{ setting('backups.keep', 10) }}.</p>
<div class="table-wrap"><table class="t"><thead><tr><th>Data</th><th>Tip</th><th>Versiune</th><th>Mărime</th><th>Verificat</th><th>Creat de</th><th>Acțiuni</th></tr></thead><tbody>
@forelse ($items as $b)
<tr><td class="small">{{ $b->created_at->format('d.m.Y H:i') }}<br><span class="muted">{{ $b->filename }}</span></td><td>{{ $b->type }}</td><td>{{ $b->app_version }}</td><td class="num">{{ number_format($b->size / 1048576, 1, ',', '') }} MB</td>
<td>@if ($b->status !== 'ok')<span class="tag tag--err">{{ $b->status }}</span>@elseif ($b->verified_at)<span class="tag tag--ok">{{ $b->verified_at->format('d.m H:i') }}</span>@else<span class="tag tag--warn">neverificat</span>@endif</td>
<td class="small">{{ $b->creator?->name ?? 'automat' }}</td>
<td>@if ($b->status === 'ok')
    <div class="btn-row">
    <form method="post" action="{{ route('admin.backups.verify', $b) }}">@csrf<button class="link-btn small" type="submit">Verifică</button></form>
    <a class="small" href="{{ route('admin.backups.download', $b) }}">Descarcă</a>
    <form method="post" action="{{ route('admin.backups.destroy', $b) }}" data-confirm="Ștergeți backup-ul?">@csrf @method('DELETE')<button class="link-btn link-btn--danger small" type="submit">Șterge</button></form>
    </div>
    <details><summary class="small">Restaurează</summary>
        <form method="post" action="{{ route('admin.backups.restore', $b) }}" style="margin-top:.5rem">@csrf
            <p class="small">Înlocuiește baza de date și fișierele cu cele din acest backup. Înainte se creează automat un backup de siguranță. Scrieți <strong>RESTAUREAZA</strong>:</p>
            <input class="input" name="confirm" autocomplete="off" required><button class="btn btn--danger btn--sm" type="submit" style="margin-top:.4rem">Restaurează</button>
        </form>
    </details>
@endif</td></tr>
@empty
<tr><td colspan="7" class="muted">Niciun backup. Creați primul backup acum.</td></tr>
@endforelse
</tbody></table></div>
<form class="panel" method="post" action="{{ route('admin.backups.upload') }}" enctype="multipart/form-data" style="margin-top:1.5rem">@csrf
    <h2>Încarcă un backup</h2><p class="small muted">Pentru mutarea pe alt server sau restaurarea dintr-o copie descărcată. Arhiva este verificată înainte de a fi acceptată.</p>
    <div class="filters"><div class="field"><label for="archive">Arhivă ZIP</label><input class="input" type="file" id="archive" name="archive" accept=".zip" required></div><button class="btn btn--line" type="submit">Încarcă și verifică</button></div>
</form>
@endsection
