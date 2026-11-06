@extends('admin.layout')
@section('title', 'Bibliotecă media')
@section('content')
<div class="page-title"><div><h1>Bibliotecă media</h1><p>Imaginile sunt reencodate (fără metadate), redimensionate la max. 2560 px și convertite în WebP pentru încărcare rapidă.</p></div></div>
@if ($missingAlt)<div class="alert alert--warn">{{ $missingAlt }} imagini nu au text alternativ. Textul alternativ descrie imaginea pentru cititoarele de ecran și pentru Google.</div>@endif
<form class="panel" method="post" action="{{ route('admin.media.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="filters">
        <div class="field"><label for="files">Încarcă imagini (JPG, PNG, WebP, max. 20 MB)</label><input class="input" id="files" name="files[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp" required></div>
        <div class="field"><label for="folder">Dosar</label><input class="input" id="folder" name="folder" placeholder="general" pattern="[a-z0-9\-]+"></div>
        <button class="btn" type="submit">Încarcă</button>
    </div>
</form>
<form class="filters" method="get">
    <div class="field"><label for="q">Caută</label><input class="input" id="q" name="q" value="{{ request('q') }}"></div>
    <div class="field"><label for="fd">Dosar</label><select class="select" id="fd" name="folder"><option value="">Toate</option>@foreach ($folders as $f)<option @selected(request('folder') === $f)>{{ $f }}</option>@endforeach</select></div>
    <button class="btn btn--line" type="submit">Filtrează</button>
</form>
<div class="media-grid">
@foreach ($items as $m)
    <div class="media-card">
        <a href="{{ $m->url() }}" target="_blank" rel="noopener"><img src="{{ $m->variantUrl(480) }}" alt="{{ $m->alt }}" loading="lazy"></a>
        <div>
            <details>
                <summary class="small">{{ \Illuminate\Support\Str::limit($m->alt ?: $m->original_name, 40) }} @if (! $m->alt)<span class="tag tag--warn">fără alt</span>@endif</summary>
                <p class="small muted">{{ $m->width }}×{{ $m->height }} px, {{ number_format($m->size / 1024, 0) }} KB, {{ count($m->variants ?? []) }} variante</p>
                <form method="post" action="{{ route('admin.media.update', $m) }}">@csrf @method('PUT')
                    <div class="field"><label for="alt{{ $m->id }}">Text alternativ</label><input class="input" id="alt{{ $m->id }}" name="alt" value="{{ $m->alt }}" maxlength="255"></div>
                    <div class="field"><label for="cap{{ $m->id }}">Descriere</label><input class="input" id="cap{{ $m->id }}" name="caption" value="{{ $m->caption }}" maxlength="255"></div>
                    <div class="field"><label for="foc{{ $m->id }}">Punct de interes (decupare)</label><select class="select" id="foc{{ $m->id }}" name="focal">@foreach (['50% 50%' => 'Centru', '50% 20%' => 'Sus', '50% 80%' => 'Jos', '20% 50%' => 'Stânga', '80% 50%' => 'Dreapta'] as $k => $l)<option value="{{ $k }}" @selected($m->focal === $k)>{{ $l }}</option>@endforeach</select></div>
                    <button class="btn btn--sm" type="submit">Salvează</button>
                </form>
                <div class="btn-row" style="margin-top:.5rem">
                    <form method="post" action="{{ route('admin.media.regenerate', $m) }}">@csrf<button class="link-btn small" type="submit">Regenerează variante</button></form>
                    <form method="post" action="{{ route('admin.media.destroy', $m) }}" data-confirm="Ștergeți imaginea?">@csrf @method('DELETE')<button class="link-btn link-btn--danger small" type="submit">Șterge</button></form>
                </div>
            </details>
        </div>
    </div>
@endforeach
</div>
{{ $items->links('admin.partials.pagination') }}
@endsection
