@extends('admin.layout')
@section('title', 'Utilizatori')
@section('content')
<div class="page-title"><div><h1>Utilizatori și roluri</h1><p>Toți utilizatorii trebuie să folosească 2FA. Rolurile limitează operațiunile pe server, nu doar meniul.</p></div><a class="btn" href="{{ route('admin.users.create') }}">Utilizator nou</a></div>
<div class="table-wrap"><table class="t"><thead><tr><th>Nume</th><th>Email</th><th>Rol</th><th>2FA</th><th>Ultima autentificare</th><th>Stare</th></tr></thead><tbody>
@foreach ($users as $u)
<tr><td><a href="{{ route('admin.users.edit', $u) }}"><strong>{{ $u->name }}</strong></a></td><td>{{ $u->email }}</td><td>{{ $u->roleLabel() }}</td>
<td>@if ($u->hasTwoFactor())<span class="tag tag--ok">activ</span>@else<span class="tag tag--warn">neconfigurat</span>@endif</td>
<td class="small">{{ $u->last_login_at?->format('d.m.Y H:i') ?? '—' }}</td><td>@if ($u->is_active)<span class="tag tag--ok">activ</span>@else<span class="tag tag--err">dezactivat</span>@endif</td></tr>
@endforeach
</tbody></table></div>
<div class="panel" style="margin-top:1.5rem"><h2>Ce poate face fiecare rol</h2><dl class="kv">
<dt>Administrator</dt><dd>Tot, inclusiv utilizatori, securitate, SMTP, Google, AI, backup și actualizări.</dd>
<dt>Editor conținut</dt><dd>Pagini, servicii, capacități, proiecte, documente, meniuri, media, SEO, redirect-uri, baza de cunoștințe AI; vede cererile.</dd>
<dt>Vânzări / ofertare</dt><dd>Cereri de ofertă: vizualizare, stare, responsabil, note, fișiere, export.</dd>
<dt>Doar vizualizare</dt><dd>Panoul principal și lista cererilor, fără fișiere și fără modificări.</dd>
</dl></div>
@endsection
