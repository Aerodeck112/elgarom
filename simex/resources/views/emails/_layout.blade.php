<!DOCTYPE html>
<html lang="{{ $lang ?? 'ro' }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>{{ $title ?? 'GRUP SIMEX' }}</title></head>
<body style="margin:0;background:#eef0f1;font-family:Arial,Helvetica,sans-serif;color:#15181b;font-size:15px;line-height:1.55">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef0f1;padding:24px 0"><tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff">
<tr><td style="background:#15181b;padding:18px 28px;color:#ffffff;font-size:17px;font-weight:bold;letter-spacing:.08em;border-bottom:3px solid #ffc40e">{{ $brand ?? setting('smtp.from_name', setting('app.name', 'GRUP SIMEX')) }}</td></tr>
<tr><td style="padding:28px">{!! $slot !!}</td></tr>
</table></td></tr></table></body></html>
