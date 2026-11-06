<!DOCTYPE html>
<html lang="ro"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>{{ $title ?? 'ELGAROM' }}</title></head>
<body style="margin:0;background:#eef0f1;font-family:Arial,Helvetica,sans-serif;color:#20262b;font-size:15px;line-height:1.5">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef0f1;padding:24px 0"><tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff">
<tr><td style="background:#20262b;padding:20px 28px;color:#ffffff;font-size:20px;font-weight:bold;letter-spacing:.02em;border-bottom:3px solid #14ad95">{{ setting('company.name', 'ELGAROM') }}</td></tr>
<tr><td style="padding:28px">{!! $slot !!}</td></tr>
<tr><td style="padding:18px 28px;background:#f7f8f8;color:#5c666e;font-size:12px">{{ setting('company.name', 'ELGAROM') }}, {{ company_address() }}@foreach (setting('company.phones', []) as $p) | {{ $p['label'] }}: {{ $p['number'] }}@endforeach</td></tr>
</table></td></tr></table></body></html>
