@component('emails._layout', ['title' => $title])
<div style="white-space:pre-line">{{ $body }}</div>
@if (! empty($link))
<p style="margin:26px 0 0"><a href="{{ $link }}" style="display:inline-block;background:#15181b;color:#ffffff;text-decoration:none;font-weight:bold;padding:12px 20px;border-radius:4px">{{ $linkLabel ?? 'Deschide oferta' }}</a></p>
@endif
@endcomponent
