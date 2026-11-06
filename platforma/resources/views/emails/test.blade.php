@component('emails._layout', ['title' => 'Test SMTP'])
<h1 style="font-size:20px;margin:0 0 16px;color:#28387b">Test SMTP reușit</h1>
<p>Acest email confirmă că setările SMTP din administrarea site-ului funcționează. Trimis la {{ now()->format('d.m.Y H:i') }}.</p>
@endcomponent
