@php
    $rows = [
        'Număr' => $q->reference, 'Tip' => $q->kind === 'contact' ? 'Mesaj (formular scurt)' : 'Cerere de ofertă',
        'Nume' => $q->name, 'Companie' => $q->company, 'Email' => $q->email, 'Telefon' => $q->phone, 'Localitate' => $q->city,
        'Tip piese' => \App\Models\QuoteRequest::PART_TYPES[$q->part_type] ?? null, 'Material' => $q->material,
        'Execuție' => \App\Models\QuoteRequest::PROCESSES[$q->process] ?? null, 'Cantitate' => $q->quantity, 'Greutate' => $q->weight,
        'Dimensiuni' => $q->dimensions, 'Termen' => trim(($q->deadline?->format('d.m.Y') ?? '').' '.($q->deadline_note ?? '')),
        'Sursa' => $q->source, 'Marketing' => $q->marketing_consent ? 'Acord dat' : 'Fără acord',
    ];
@endphp
@component('emails._layout', ['title' => $q->reference])
<h1 style="font-size:20px;margin:0 0 16px;color:#28387b">{{ $q->kind === 'contact' ? 'Mesaj nou' : 'Cerere de ofertă nouă' }} – {{ $q->reference }}</h1>
<table role="presentation" width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:14px">
@foreach ($rows as $k => $v)@if ($v)<tr><td style="color:#5c666e;width:130px;border-bottom:1px solid #eef0f1;vertical-align:top">{{ $k }}</td><td style="border-bottom:1px solid #eef0f1">{{ $v }}</td></tr>@endif @endforeach
</table>
<h2 style="font-size:16px;margin:20px 0 8px">Descriere</h2>
<p style="white-space:pre-wrap;margin:0 0 16px">{{ $q->description }}</p>
@if ($q->files->isNotEmpty())<p style="margin:0 0 16px">Fișiere atașate: {{ $q->files->count() }} (disponibile doar în panoul de administrare).</p>@endif
<p><a href="{{ url('/admin/cereri/'.$q->id) }}" style="display:inline-block;background:#14ad95;color:#20262b;padding:12px 18px;text-decoration:none;font-weight:bold">Deschide cererea în administrare</a></p>
@endcomponent
