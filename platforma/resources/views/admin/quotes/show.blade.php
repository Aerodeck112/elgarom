@extends('admin.layout')
@section('title', $q->reference)
@section('content')
@php $canManage = auth()->user()->can_('quotes.manage'); @endphp
<div class="page-title"><div><p><a href="{{ route('admin.quotes.index') }}">Cereri</a></p><h1>{{ $q->reference }}</h1><p>{{ $q->kind === 'contact' ? 'Mesaj din formularul scurt' : 'Cerere de ofertă' }}, primită {{ $q->created_at->format('d.m.Y H:i') }}@if ($q->source === 'asistent_ai'), precompletată cu asistentul AI @endif</p></div>
<span class="tag {{ $q->status === 'nou' ? 'tag--info' : '' }}" style="font-size:1rem">{{ $q->statusLabel() }}</span></div>
<div class="grid" style="grid-template-columns:minmax(0,2fr) minmax(0,1fr)">
<div>
    <div class="panel"><h2>Client</h2><dl class="kv">
        <dt>Nume</dt><dd>{{ $q->name }}</dd>
        <dt>Companie</dt><dd>{{ $q->company ?: '—' }}</dd>
        <dt>Email</dt><dd><a href="mailto:{{ $q->email }}?subject={{ rawurlencode($q->reference) }}">{{ $q->email }}</a></dd>
        <dt>Telefon</dt><dd>@if ($q->phone)<a href="{{ tel_href($q->phone) }}">{{ $q->phone }}</a>@else — @endif</dd>
        <dt>Localitate</dt><dd>{{ $q->city ?: '—' }}</dd>
        <dt>Acord marketing</dt><dd>{{ $q->marketing_consent ? 'Da' : 'Nu' }}</dd>
        <dt>Politica acceptată</dt><dd>{{ $q->privacy_accepted_at?->format('d.m.Y H:i') }}</dd>
    </dl></div>
    <div class="panel"><h2>Piese și cerințe</h2><dl class="kv">
        <dt>Tip piese</dt><dd>{{ \App\Models\QuoteRequest::PART_TYPES[$q->part_type] ?? '—' }}</dd>
        <dt>Material</dt><dd>{{ $q->material ?: '—' }}</dd>
        <dt>Execuție</dt><dd>{{ \App\Models\QuoteRequest::PROCESSES[$q->process] ?? '—' }}</dd>
        <dt>Cantitate</dt><dd>{{ $q->quantity ?: '—' }}</dd>
        <dt>Greutate</dt><dd>{{ $q->weight ?: '—' }}</dd>
        <dt>Dimensiuni</dt><dd>{{ $q->dimensions ?: '—' }}</dd>
        <dt>Termen</dt><dd>{{ $q->deadline?->format('d.m.Y') ?: '—' }} {{ $q->deadline_note }}</dd>
        <dt>Descriere</dt><dd class="pre">{{ $q->description }}</dd>
    </dl></div>
    <div class="panel"><h2>Fișiere ({{ $q->files->count() }})</h2>
        @if ($q->files->isEmpty())<p class="muted">Fără fișiere.</p>@else
            @if (auth()->user()->can_('quotes.files'))
            <ul>@foreach ($q->files as $f)<li><a href="{{ route('admin.quotes.file', [$q, $f]) }}">{{ $f->original_name }}</a> <span class="small muted">({{ number_format($f->size / 1048576, 2, ',', '') }} MB, SHA-256 {{ substr($f->sha256, 0, 12) }}…)</span>
                @if (in_array($f->mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true)) – <a href="{{ route('admin.quotes.file', [$q, $f]) }}?view=1" target="_blank" rel="noopener">vizualizează</a>@endif</li>@endforeach</ul>
            @else<p class="muted">Nu aveți permisiunea de a descărca fișierele.</p>@endif
        @endif
    </div>
    <div class="panel"><h2>Istoric și note</h2>
        @if ($canManage)
        <form method="post" action="{{ route('admin.quotes.note', $q) }}">@csrf<div class="field"><label for="note">Notă internă</label><textarea class="textarea" id="note" name="body" rows="3" required maxlength="4000"></textarea></div><button class="btn btn--line btn--sm" type="submit">Adaugă nota</button></form>
        @endif
        <ul style="margin-top:1rem">@foreach ($q->events as $ev)<li><span class="small muted">{{ $ev->created_at->format('d.m.Y H:i') }}{{ $ev->user ? ', '.$ev->user->name : '' }}:</span> <span class="pre">{{ $ev->body }}</span></li>@endforeach</ul>
    </div>
</div>
<div>
    @if ($canManage)
    <form class="panel" method="post" action="{{ route('admin.quotes.update', $q) }}">@csrf @method('PUT')
        <h2>Gestionare</h2>
        <div class="field"><label for="status">Stare</label><select class="select" id="status" name="status">@foreach (\App\Models\QuoteRequest::STATUSES as $k => $l)<option value="{{ $k }}" @selected($q->status === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="field"><label for="assignee">Responsabil</label><select class="select" id="assignee" name="assignee_id"><option value="">Nealocat</option>@foreach ($users as $u)<option value="{{ $u->id }}" @selected($q->assignee_id === $u->id)>{{ $u->name }}</option>@endforeach</select></div>
        <button class="btn" type="submit">Salvează</button>
    </form>
    @endif
    <div class="panel"><h2>Emailuri</h2>
        @forelse ($mails as $m)<p class="small" style="margin:.3rem 0">{{ $m->to_email }}: <span class="tag {{ $m->status === 'sent' ? 'tag--ok' : ($m->status === 'failed' ? 'tag--err' : 'tag--warn') }}">{{ ['sent' => 'trimis', 'pending' => 'în așteptare', 'failed' => 'eșuat', 'sending' => 'se trimite'][$m->status] ?? $m->status }}</span></p>@empty<p class="muted small">Niciun email.</p>@endforelse
    </div>
    @if (auth()->user()->isAdmin())
    <form class="panel panel--err" method="post" action="{{ route('admin.quotes.destroy', $q) }}" data-confirm="Ștergeți definitiv cererea și fișierele (de ex. la cererea clientului, GDPR)?">@csrf @method('DELETE')
        <h2>Ștergere (GDPR)</h2><p class="small">Șterge definitiv cererea, notele și fișierele.</p><button class="btn btn--danger btn--sm" type="submit">Șterge definitiv</button>
    </form>
    @endif
</div>
</div>
@endsection
