@extends('admin.layout')
@section('title', $entity->name)
@section('content')
@php $F = fn ($n, $l, $t = 'text', $x = []) => ['name' => $n, 'label' => $l, 'type' => $t] + $x; @endphp
<div class="page-head"><div><p class="eyebrow"><a href="{{ route('admin.entities.index') }}">Companiile grupului</a></p><h1 class="page-title">{{ $entity->name }}</h1></div>
    @if ($entity->legal_confirmed_at)<span class="tag tag--ok">Date juridice confirmate la {{ $entity->legal_confirmed_at->format('d.m.Y') }}</span>@else<span class="pending">Date juridice neconfirmate</span>@endif</div>
<div class="cols">
<form class="stack" method="post" action="{{ route('admin.entities.update', $entity) }}" enctype="multipart/form-data">@csrf @method('PUT')
    <div class="panel form-grid"><h2 style="grid-column:span 12">Prezentare publică</h2>
        @foreach ([$F('name', 'Nume afișat', 'text', ['rules' => 'required', 'span' => 6]), $F('activity', 'Activitate (o linie)', 'text', ['span' => 6]), $F('description', 'Descriere scurtă', 'textarea', ['rows' => 3]),
            $F('website', 'Website', 'url', ['span' => 6]), $F('accent_color', 'Culoare de accent (#RRGGBB)', 'text', ['span' => 3]), $F('sort', 'Ordine', 'number', ['span' => 3])] as $f)
            @include('admin.partials.field', ['f' => $f, 'value' => $entity->{$f['name']}])
        @endforeach
        <div class="field col-6"><label for="logo">Siglă oficială (PNG, JPG, WebP sau SVG)</label><input class="input" id="logo" name="logo" type="file" accept=".png,.jpg,.jpeg,.webp,.svg">@error('logo')<p class="error-text">{{ $message }}</p>@enderror<p class="hint">Încărcați doar sigla autentică a companiei, din sursa oficială.</p></div>
        <div class="field col-6"><label class="check"><input type="checkbox" name="show_on_site" value="1" @checked($entity->show_on_site)> Afișează pe website-ul public</label><label class="check"><input type="checkbox" name="is_issuer" value="1" @checked($entity->is_issuer)> Poate emite oferte</label><label class="check"><input type="checkbox" name="logo_on_dark" value="1" @checked($entity->logo_on_dark)> Sigla este albă (se afișează pe fundal închis)</label></div>
    </div>
    <div class="panel form-grid"><h2 style="grid-column:span 12">Date juridice și bancare</h2>
        <p class="hint" style="grid-column:span 12">Apar pe oferte și în subsolul website-ului. Nu sunt presupuse: se completează din documentele societății. Orice modificare anulează confirmarea.</p>
        @foreach ([$F('legal_name', 'Denumire juridică', 'text', ['span' => 6]), $F('tax_id', 'Cod fiscal (CUI)', 'text', ['span' => 3]), $F('reg_no', 'Nr. Registrul Comerțului', 'text', ['span' => 3]),
            $F('address', 'Adresă (stradă, număr)', 'text', ['span' => 6]), $F('city', 'Localitate', 'text', ['span' => 3]), $F('county', 'Județ', 'text', ['span' => 3]),
            $F('postal_code', 'Cod poștal', 'text', ['span' => 3]), $F('phone', 'Telefon', 'text', ['span' => 3]), $F('email', 'Email', 'email', ['span' => 6]),
            $F('bank', 'Bancă', 'text', ['span' => 6]), $F('iban', 'IBAN', 'text', ['span' => 4]), $F('quote_prefix', 'Prefix număr ofertă', 'text', ['span' => 2, 'hint' => 'Litere mari / cifre.'])] as $f)
            @include('admin.partials.field', ['f' => $f, 'value' => $entity->{$f['name']}])
        @endforeach
    </div>
    <div><button class="btn" type="submit">Salvează</button></div>
</form>
<div class="stack">
    <div class="panel">@if ($entity->logoUrl())<h2>Sigla curentă</h2><div style="background:#15181b;padding:16px;border-radius:4px;text-align:center"><img src="{{ $entity->logoUrl() }}" alt="" style="max-width:100%;max-height:70px"></div><div style="background:#fff;border:1px solid var(--line);padding:16px;border-radius:4px;text-align:center;margin-top:8px"><img src="{{ $entity->logoUrl() }}" alt="" style="max-width:100%;max-height:70px"></div>@else<h2>Siglă</h2><p class="pending">Neîncărcată</p><p class="small muted">Până la încărcare, pe website apare numele companiei ca text.</p>@endif</div>
    <form class="panel {{ $entity->legal_confirmed_at ? 'panel--ok' : 'panel--warn' }}" method="post" action="{{ route('admin.entities.confirm', $entity) }}">@csrf<h2>Confirmarea datelor juridice</h2>
        <label class="check small"><input type="checkbox" name="confirm" value="1" required> Am verificat denumirea, codul fiscal, numărul de registru și adresa în documentele oficiale ale societății.</label>
        <p style="margin-top:10px"><button class="btn btn--sm" type="submit">Confirmă datele</button></p><p class="hint">Confirmarea cere parola și codul 2FA și este trecută în jurnalul de audit.</p></form>
</div></div>
@endsection
