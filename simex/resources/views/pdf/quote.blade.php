@php
    $q = $s['quote']; $iss = $s['issuer']; $cl = $s['client']; $accent = $iss['accent_color'] ?: '#ffc40e';
    $m = fn ($v, $d = 2) => number_format((float) $v, $d, $lang === 'en' ? '.' : ',', $lang === 'en' ? ',' : '.');
    $date = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('d.m.Y') : '—';
    $hasDiscount = collect($s['items'])->contains(fn ($i) => (float) $i['discount_percent'] > 0);
    $hasOptional = collect($s['items'])->contains(fn ($i) => $i['is_optional']);
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}"><head><meta charset="utf-8"><title>{{ __('quote.title') }} {{ $q['number'] }}</title>
<style>
    @page { margin: 26mm 16mm 22mm 16mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #15181b; line-height: 1.4; }
    .head { position: fixed; top: -19mm; left: 0; right: 0; height: 15mm; border-bottom: 1.5pt solid {{ $accent }}; }
    .head td { vertical-align: middle; } .head img { max-height: 12mm; max-width: 60mm; }
    .brand { font-size: 13pt; font-weight: bold; letter-spacing: 1pt; }
    .foot { position: fixed; bottom: -15mm; left: 0; right: 30mm; font-size: 7pt; color: #6b737c; border-top: 0.5pt solid #c5cad0; padding-top: 2mm; }
    h1 { font-size: 17pt; margin: 0 0 1mm; letter-spacing: -0.3pt; } h2 { font-size: 8pt; text-transform: uppercase; letter-spacing: 1pt; color: #6b737c; margin: 0 0 1.5mm; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; } .meta td { padding: 0 0 1mm; } .box { width: 48%; vertical-align: top; }
    .items { margin-top: 6mm; } .items th { font-size: 7pt; text-transform: uppercase; letter-spacing: 0.6pt; text-align: left; color: #6b737c; border-bottom: 1pt solid #15181b; padding: 2mm 1.5mm; }
    .items td { padding: 2mm 1.5mm; border-bottom: 0.5pt solid #dfe2e6; vertical-align: top; } .items tr { page-break-inside: avoid; }
    .r { text-align: right; white-space: nowrap; } .small { font-size: 7.5pt; color: #5b636b; } .tot td { padding: 1.2mm 1.5mm; } .grand td { border-top: 1pt solid #15181b; font-weight: bold; font-size: 10.5pt; padding-top: 2mm; }
    .draft { position: fixed; top: 95mm; left: 0; right: 0; text-align: center; font-size: 34pt; color: #e6e8ea; font-weight: bold; transform: rotate(-24deg); z-index: -1; }
    .terms td { padding: 1mm 0; vertical-align: top; } .terms td:first-child { width: 42mm; color: #6b737c; } .block { page-break-inside: avoid; margin-top: 7mm; }
</style></head><body>
@if ($draft)<div class="draft">{{ __('quote.draft') }}</div>@endif
<table class="head"><tr><td>@if ($logo)@if (! empty($iss['logo_on_dark']))<span style="background:#15181b;padding:1.5mm 3mm;display:inline-block"><img src="{{ $logo }}" alt=""></span>@else<img src="{{ $logo }}" alt="">@endif @else<span class="brand">{{ $iss['name'] }}</span>@endif</td><td class="r small">{{ $iss['website'] }}<br>{{ $iss['email'] }} @if ($iss['phone'])· {{ $iss['phone'] }}@endif</td></tr></table>
<div class="foot">{{ $iss['legal_name'] ?: $iss['name'] }} @if ($iss['tax_id'])· {{ __('quote.tax_id') }} {{ $iss['tax_id'] }}@endif @if ($iss['reg_no'])· {{ __('quote.reg_no') }} {{ $iss['reg_no'] }}@endif @if ($iss['address'])· {{ collect([$iss['address'], $iss['postal_code'], $iss['city'], $iss['county']])->filter()->implode(', ') }}@endif @if ($iss['iban'])<br>{{ __('quote.bank') }}: {{ $iss['bank'] }} · IBAN {{ $iss['iban'] }}@endif</div>

<table><tr><td style="vertical-align:top"><h1>{{ __('quote.title') }}</h1>@if ($q['title'])<div>{{ $q['title'] }}</div>@endif</td>
<td style="width:70mm;vertical-align:top"><table class="meta">
    <tr><td class="small">{{ __('quote.number') }}</td><td class="r"><strong>{{ $q['number'] }}</strong>@if ($q['revision']) · {{ __('quote.revision') }} {{ $q['revision'] }}@endif</td></tr>
    <tr><td class="small">{{ __('quote.date') }}</td><td class="r">{{ $date($q['issued_at'] ?? null) }}</td></tr><tr><td class="small">{{ __('quote.valid_until') }}</td><td class="r">{{ $date($q['valid_until'] ?? null) }}</td></tr></table></td></tr></table>
<table style="margin-top:6mm"><tr>
    <td class="box"><h2>{{ __('quote.issuer') }}</h2><strong>{{ $iss['legal_name'] ?: $iss['name'] }}</strong><br>{{ collect([$iss['address'], $iss['postal_code'], $iss['city']])->filter()->implode(', ') }}@if ($iss['tax_id'])<br>{{ __('quote.tax_id') }} {{ $iss['tax_id'] }}@endif
        @if ($s['owner'])<br><br><span class="small">{{ __('quote.contact') }}</span><br>{{ $s['owner']['name'] }}@if ($s['owner']['job_title']), {{ $s['owner']['job_title'] }}@endif<br>{{ $s['owner']['email'] }} @if ($s['owner']['phone'])· {{ $s['owner']['phone'] }}@endif @endif</td><td style="width:4%"></td>
    <td class="box"><h2>{{ __('quote.client') }}</h2><strong>{{ $cl['legal_name'] ?: $cl['name'] }}</strong><br>{{ collect([$cl['address'], $cl['postal_code'], $cl['city'], $cl['country']])->filter()->implode(', ') }}@if ($cl['tax_id'])<br>{{ __('quote.tax_id') }} {{ $cl['tax_id'] }}@endif
        @if ($s['contact'])<br><br><span class="small">{{ __('quote.attention') }}</span><br>{{ $s['contact']['name'] }}@if ($s['contact']['position']), {{ $s['contact']['position'] }}@endif @endif</td></tr></table>
@if ($q['intro'])<p style="margin-top:6mm;white-space:pre-line">{{ $q['intro'] }}</p>@endif
<table class="items"><thead><tr><th style="width:7mm">{{ __('quote.pos') }}</th><th style="width:24mm">{{ __('quote.code') }}</th><th>{{ __('quote.description') }}</th><th class="r">{{ __('quote.qty') }}</th><th class="r">{{ __('quote.unit_price') }}</th>@if ($hasDiscount)<th class="r">{{ __('quote.discount') }}</th>@endif<th class="r">{{ __('quote.total') }} {{ $q['currency'] }}</th></tr></thead><tbody>
@foreach ($s['items'] as $i)
    <tr><td>{{ $i['position'] }}</td><td>{{ $i['code'] }}</td><td><strong>{{ $i['name'] }}</strong>@if ($i['is_optional']) <span class="small">({{ __('quote.optional') }})</span>@endif @if ($i['description'])<div class="small" style="white-space:pre-line">{{ $i['description'] }}</div>@endif @if ($i['lead_time'])<div class="small">{{ __('quote.lead_time') }}: {{ $i['lead_time'] }}</div>@endif</td>
        <td class="r">{{ rtrim(rtrim($m($i['quantity'], 3), '0'), ',.') }} {{ $i['unit'] }}</td><td class="r">{{ $m($i['unit_price'], fmod((float) $i['unit_price'] * 100, 1) > 0.0001 ? 4 : 2) }}</td>@if ($hasDiscount)<td class="r">{{ (float) $i['discount_percent'] > 0 ? $m($i['discount_percent'], 1).' %' : '' }}</td>@endif<td class="r">{{ $m($i['line_total']) }}</td></tr>
@endforeach</tbody></table>
<table style="margin-top:3mm"><tr><td>@if ($hasOptional)<span class="small">{{ __('quote.optional_note') }}</span>@endif</td><td style="width:78mm"><table class="tot">
    @if ((float) $q['discount_percent'] > 0)<tr><td>{{ __('quote.subtotal') }}</td><td class="r">{{ $m($q['subtotal']) }}</td></tr><tr><td>{{ __('quote.discount') }} {{ $m($q['discount_percent'], 1) }} %</td><td class="r">−{{ $m($q['subtotal'] - $q['total_net']) }}</td></tr>@endif
    <tr><td>{{ __('quote.net') }}</td><td class="r">{{ $m($q['total_net']) }} {{ $q['currency'] }}</td></tr><tr><td>{{ __('quote.vat') }} {{ $m($q['vat_rate'], 1) }} %</td><td class="r">{{ $m($q['vat_amount']) }} {{ $q['currency'] }}</td></tr>
    <tr class="grand"><td>{{ __('quote.gross') }}</td><td class="r">{{ $m($q['total_gross']) }} {{ $q['currency'] }}</td></tr></table></td></tr></table>
<div class="block"><h2>{{ __('quote.terms') }}</h2><table class="terms">
    @foreach (['lead_time' => $q['lead_time'], 'payment' => $q['payment_terms'], 'delivery' => trim(($q['delivery_terms'] ?? '').' '.($q['incoterm'] ?? '')), 'min_order' => $q['min_order']] as $k => $v) @if (filled($v))<tr><td>{{ __('quote.'.$k) }}</td><td>{{ $v }}</td></tr>@endif @endforeach
    @if ($q['exchange_rate'])<tr><td>{{ __('quote.exchange') }}</td><td>1 EUR = {{ $m($q['exchange_rate'], 4) }} RON @if ($q['exchange_rate_date'] ?? null)({{ $date($q['exchange_rate_date']) }})@endif</td></tr>@endif</table></div>
@if ($q['notes'])<div class="block"><h2>{{ __('quote.notes') }}</h2><div style="white-space:pre-line">{{ $q['notes'] }}</div></div>@endif
<div class="block" style="margin-top:14mm"><table><tr><td></td><td style="width:70mm;border-top:0.5pt solid #15181b;padding-top:2mm" class="small">{{ __('quote.signature') }}<br>{{ $iss['legal_name'] ?: $iss['name'] }}</td></tr></table></div>
</body></html>
