@extends('admin.layout')
@section('title', $company->name)
@section('content')
@php use App\Support\Vocab; $manage = can('crm.manage'); @endphp
<div class="page-head">
    <div><p class="eyebrow"><a href="{{ route('admin.companies.index') }}">Clienți și contacte</a></p><h1 class="page-title">{{ $company->name }}</h1>
        <div class="btn-row" style="margin-top:6px"><span class="tag tag--{{ Vocab::tone($company->stage) }}">{{ $company->stageLabel() }}</span><span class="tag">{{ Vocab::COMPANY_KINDS[$company->kind] ?? $company->kind }}</span>@if ($company->do_not_contact)<span class="tag tag--err">nu contacta</span>@endif</div></div>
    <div class="btn-row">
        @if (can('rfq.manage'))<a class="btn btn--line" href="{{ route('admin.rfqs.create', ['company' => $company->id]) }}">Cerere de ofertă nouă</a>@endif
        @if (can('quotes.manage'))<a class="btn btn--line" href="{{ route('admin.quotes.create', ['company' => $company->id]) }}">Ofertă nouă</a>@endif
        @if ($manage)<a class="btn" href="{{ route('admin.companies.edit', $company) }}">Modifică</a>@endif
    </div>
</div>
<div class="cols">
<div class="stack">
    <div class="panel panel--flush"><div class="panel__title">Persoane de contact @if ($manage)<button class="btn btn--sm btn--line" type="button" data-open="#contact-new">Adaugă contact</button>@endif</div>
        <table class="t"><thead><tr><th>Nume</th><th>Funcție</th><th>Email</th><th>Telefon</th><th>Limbă</th><th></th></tr></thead><tbody>
        @forelse ($company->contacts as $c)
            <tr><td>{{ $c->fullName() }} @if ($c->is_primary)<span class="tag tag--accent">principal</span>@endif @if ($c->do_not_contact || $c->unsubscribed_at)<span class="tag tag--err">exclus</span>@endif</td>
                <td>{{ $c->position ?: '—' }}</td><td>@if ($c->email)<a class="link" href="mailto:{{ $c->email }}">{{ $c->email }}</a>@else — @endif</td><td>{{ $c->phone ?: ($c->mobile ?: '—') }}</td><td class="mono small">{{ strtoupper($c->language) }}</td>
                <td class="actions">@if ($manage)<button class="link-btn" type="button" data-open="#contact-{{ $c->id }}">Modifică</button>@endif</td></tr>
        @empty <tr><td colspan="6"><div class="empty">Niciun contact înregistrat.</div></td></tr>
        @endforelse
        </tbody></table></div>

    <div class="panel panel--flush"><div class="panel__title">Cereri de ofertă</div><table class="t"><tbody>
        @forelse ($company->rfqs as $r)<tr><td class="mono"><a class="link" href="{{ route('admin.rfqs.show', $r) }}">{{ $r->number }}</a></td><td>{{ $r->title }}</td><td><span class="tag tag--{{ Vocab::tone($r->status) }}">{{ $r->statusLabel() }}</span></td><td class="mono small">{{ $r->due_at?->format('d.m.Y') ?? '—' }}</td></tr>
        @empty <tr><td><div class="empty">Nicio cerere.</div></td></tr> @endforelse</tbody></table></div>

    @if (can('quotes.view'))
    <div class="panel panel--flush"><div class="panel__title">Oferte</div><table class="t"><tbody>
        @forelse ($company->quotes as $q)<tr><td class="mono"><a class="link" href="{{ route('admin.quotes.show', $q) }}">{{ $q->label() }}</a></td><td>{{ $q->title ?: '—' }}</td><td><span class="tag tag--{{ Vocab::tone($q->status) }}">{{ $q->statusLabel() }}</span></td><td class="num">{{ money($q->total_net) }} {{ $q->currency }}</td></tr>
        @empty <tr><td><div class="empty">Nicio ofertă.</div></td></tr> @endforelse</tbody></table></div>
    @endif

    <div class="panel"><h2>Istoric comunicări și sarcini</h2>
        @if ($manage)
        <form class="form-grid" method="post" action="{{ route('admin.activities.store', $company) }}" style="margin-bottom:16px">@csrf
            <div class="field col-3"><label for="a-type">Tip</label><select class="select" id="a-type" name="type">@foreach (array_diff_key(Vocab::ACTIVITY_TYPES, ['sistem' => 1]) as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
            <div class="field col-6"><label for="a-title">Subiect</label><input class="input" id="a-title" name="title" required maxlength="255"></div>
            <div class="field col-3"><label for="a-due">Termen (pentru sarcini)</label><input class="input" id="a-due" name="due_at" type="date"></div>
            <div class="field col-8"><label for="a-body">Detalii</label><textarea class="textarea" id="a-body" name="body" rows="2"></textarea></div>
            <div class="field col-4"><label for="a-user">Atribuit</label><select class="select" id="a-user" name="assigned_to"><option value="">—</option>@foreach ($users as $id => $name)<option value="{{ $id }}" @selected($id === auth()->id())>{{ $name }}</option>@endforeach</select></div>
            <div class="col-12"><button class="btn btn--sm" type="submit">Înregistrează</button></div>
        </form>
        @endif
        <ul class="timeline">
        @forelse ($company->activities as $a)
            <li @class(['is-task' => $a->isOpenTask()])><div class="btn-row"><strong>{{ $a->title }}</strong><span class="tag">{{ Vocab::ACTIVITY_TYPES[$a->type] ?? $a->type }}</span>
                @if ($a->isOpenTask())<span class="tag tag--warn">termen {{ $a->due_at?->format('d.m.Y') ?? 'nedefinit' }}</span>@if ($manage)<form method="post" action="{{ route('admin.activities.done', [$company, $a]) }}">@csrf<button class="link-btn small" type="submit">Marchează rezolvată</button></form>@endif @endif</div>
                @if ($a->body)<div class="small wrap" style="white-space:pre-line">{{ $a->body }}</div>@endif<div class="small muted">{{ $a->created_at->format('d.m.Y H:i') }} · {{ $a->user?->name ?? 'sistem' }}</div></li>
        @empty <li class="muted">Nicio activitate înregistrată.</li>
        @endforelse
        </ul>
    </div>
</div>
<div class="stack">
    <div class="panel"><h2>Date companie</h2><dl class="kv">
        <dt>Denumire juridică</dt><dd>{{ $company->legal_name ?: '—' }}</dd><dt>Țară</dt><dd>{{ $company->countryName() ?: '—' }}</dd>
        <dt>Adresă</dt><dd>{{ collect([$company->address, $company->postal_code, $company->city, $company->county])->filter()->implode(', ') ?: '—' }}</dd>
        <dt>Identificator fiscal</dt><dd class="mono">{{ $company->tax_id ?: '—' }}</dd><dt>Domeniu</dt><dd>{{ $company->industry ?: '—' }}</dd>
        <dt>Website</dt><dd>@if ($company->website)<a class="link" href="{{ safe_url($company->website) }}" target="_blank" rel="noopener noreferrer">{{ $company->website }}</a>@else — @endif</dd>
        <dt>Email / telefon</dt><dd>{{ $company->email ?: '—' }} · {{ $company->phone ?: '—' }}</dd><dt>Limbă</dt><dd>{{ config('simex.languages')[$company->language] ?? $company->language }}</dd>
        <dt>Sursa leadului</dt><dd>{{ Vocab::LEAD_SOURCES[$company->source] ?? '—' }}</dd>
        <dt>Interese</dt><dd>{{ collect($company->interests)->map(fn ($i) => Vocab::SERVICES[$i] ?? $i)->implode(', ') ?: '—' }}</dd>
        <dt>Responsabil</dt><dd>{{ $company->owner?->name ?? '—' }}</dd><dt>Follow-up</dt><dd>{{ $company->next_follow_up_at?->format('d.m.Y') ?? '—' }}</dd>
        <dt>Baza legală</dt><dd>{{ Vocab::LAWFUL_BASES[$company->lawful_basis] ?? 'nedefinită' }}</dd>
    </dl>@if ($company->notes)<p class="small wrap" style="white-space:pre-line;margin-top:12px">{{ $company->notes }}</p>@endif</div>
    @if (can('crm.delete'))<form class="panel" method="post" action="{{ route('admin.companies.destroy', $company) }}" data-confirm="Arhivați compania? Ofertele și cererile rămân în istoric.">@csrf @method('DELETE')<h2>Arhivare</h2><button class="btn btn--danger btn--sm" type="submit">Arhivează compania</button></form>@endif
</div>
</div>
@if ($manage)
@foreach ($company->contacts->push(new \App\Models\Contact(['language' => $company->language])) as $c)
<dialog class="modal" id="contact-{{ $c->exists ? $c->id : 'new' }}">
    <form method="post" action="{{ $c->exists ? route('admin.contacts.update', [$company, $c]) : route('admin.contacts.store', $company) }}">@csrf @if ($c->exists) @method('PUT') @endif
        <div class="modal__head">{{ $c->exists ? 'Modifică contactul' : 'Contact nou' }}<button class="link-btn" type="button" data-close>Închide</button></div>
        <div class="modal__body form-grid">
            <div class="field"><label>Prenume</label><input class="input" name="first_name" value="{{ $c->first_name }}"></div><div class="field"><label>Nume <span class="req">*</span></label><input class="input" name="last_name" value="{{ $c->last_name }}" required></div>
            <div class="field"><label>Funcție</label><input class="input" name="position" value="{{ $c->position }}"></div><div class="field"><label>Email</label><input class="input" type="email" name="email" value="{{ $c->email }}"></div>
            <div class="field"><label>Telefon</label><input class="input" name="phone" value="{{ $c->phone }}"></div><div class="field"><label>Mobil</label><input class="input" name="mobile" value="{{ $c->mobile }}"></div>
            <div class="field"><label>Limbă</label><select class="select" name="language">@foreach (config('simex.languages') as $k => $l)<option value="{{ $k }}" @selected($c->language === $k)>{{ $l }}</option>@endforeach</select></div>
            <div class="field"><label class="check"><input type="checkbox" name="is_primary" value="1" @checked($c->is_primary)> Contact principal</label><label class="check"><input type="checkbox" name="do_not_contact" value="1" @checked($c->do_not_contact)> Nu contacta comercial</label></div>
        </div>
        <div class="modal__foot"><button class="btn" type="submit">Salvează</button></div>
    </form>
    @if ($c->exists)<form method="post" action="{{ route('admin.contacts.destroy', [$company, $c]) }}" data-confirm="Ștergeți contactul?" style="padding:0 18px 14px">@csrf @method('DELETE')<button class="link-btn link-btn--danger small" type="submit">Șterge contactul</button></form>@endif
</dialog>
@endforeach
@endif
@endsection
