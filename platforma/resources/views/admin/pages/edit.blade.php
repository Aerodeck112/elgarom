@extends('admin.layout')
@section('title', $page->title)
@section('content')
<div class="page-title">
    <div><p><a href="{{ route('admin.pages.index', ['locale' => $page->locale]) }}">Pagini</a></p><h1>{{ $page->title }} <span class="tag">{{ \App\Support\Locales::all()[$page->locale]['name'] ?? $page->locale }}</span></h1>
        <p>@if ($page->status === 'published')<span class="tag tag--ok">Publicată {{ $page->published_at?->format('d.m.Y H:i') }}</span>@else<span class="tag">Ciornă</span>@endif
           @if ($page->has_unpublished_changes) <span class="tag tag--warn">Există modificări nepublicate</span>@endif</p></div>
    <div class="btn-row">
        <a class="btn btn--line" href="{{ route('admin.pages.preview-link', $page) }}" target="_blank" rel="noopener">Previzualizează ciorna</a>
        <form method="post" action="{{ route('admin.pages.publish', $page) }}">@csrf<button class="btn" type="submit">Publică</button></form>
    </div>
</div>

<div class="panel">
    <h2>Alte limbi</h2>
    <ul class="lang-list">
    @foreach (\App\Support\Locales::all() as $code => $l)
        @continue($code === $page->locale)
        <li><strong>{{ $l['name'] }}:</strong>
            @if (isset($translations[$code]))
                <a href="{{ route('admin.pages.edit', $translations[$code]) }}">{{ $translations[$code]->title }}</a>
                @if ($translations[$code]->status === 'published')<span class="tag tag--ok">publicată</span>@else<span class="tag">ciornă</span>@endif
                @if ($translations[$code]->has_unpublished_changes)<span class="tag tag--warn">modificări nepublicate</span>@endif
            @else
                <span class="muted">nu există</span>
                <form method="post" action="{{ route('admin.pages.translate', $page) }}" style="display:inline">@csrf<input type="hidden" name="locale" value="{{ $code }}"><button class="link-btn" type="submit">creează traducerea</button></form>
            @endif
        </li>
    @endforeach
    </ul>
    <p class="hint">Versiunile aceleiași pagini în limbi diferite sunt legate între ele: comutatorul de limbă de pe site și etichetele hreflang trimit la pagina corespunzătoare.</p>
</div>

<form class="panel" method="post" action="{{ route('admin.pages.update', $page) }}">
    @csrf @method('PUT')
    <h2>Date pagină și SEO</h2>
    <div class="grid-2">
        <div class="field"><label for="p-title">Titlu (H1)</label><input class="input" id="p-title" name="title" required maxlength="160" value="{{ old('title', $page->title) }}"></div>
        <div class="field"><label for="p-nav">Titlu scurt (meniu)</label><input class="input" id="p-nav" name="nav_title" maxlength="60" value="{{ old('nav_title', $page->nav_title) }}"></div>
    </div>
    <div class="field"><label for="p-lead">Text introductiv</label><textarea class="textarea" id="p-lead" name="lead" rows="3" maxlength="500">{{ old('lead', $page->lead) }}</textarea></div>
    @unless ($page->slug === '/' || ($page->is_system && $page->locale === \App\Support\Locales::default()))
        <div class="field"><label for="p-slug">Adresă URL</label><input class="input" id="p-slug" name="slug" value="{{ old('slug', $page->slug) }}"><p class="hint">La schimbare se creează automat un redirect 301 de la adresa veche.</p></div>
    @endunless
    <div class="grid-2">
        <div class="field"><label for="p-mt">Titlu SEO <span class="muted">(max. 70)</span></label><input class="input" id="p-mt" name="meta_title" maxlength="70" value="{{ old('meta_title', $page->meta_title) }}"></div>
        <div class="field"><label for="p-md">Descriere SEO <span class="muted">(max. 170)</span></label><textarea class="textarea" id="p-md" name="meta_description" rows="2" maxlength="170">{{ old('meta_description', $page->meta_description) }}</textarea></div>
    </div>
    @include('admin.fields.field', ['f' => ['name' => 'og_image_id', 'label' => 'Imagine pentru distribuire (Open Graph)', 'type' => 'media'], 'name' => 'og_image_id', 'value' => old('og_image_id', $page->og_image_id)])
    @include('admin.fields.field', ['f' => ['name' => 'noindex', 'label' => 'Exclude din motoarele de căutare (noindex)', 'type' => 'checkbox'], 'name' => 'noindex', 'value' => old('noindex', $page->noindex)])
    <button class="btn" type="submit">Salvează în ciornă</button>
</form>

<h2>Secțiuni</h2>
<div class="sections">
@forelse ($page->sections as $s)
    @php $def = $types[$s->type] ?? null; @endphp
    <details class="sec-item {{ $s->is_visible ? '' : 'sec-item--hidden' }}" id="s{{ $s->id }}" @if (request()->fullUrl() && str_contains(url()->previous(), '#s'.$s->id)) open @endif>
        <summary><span><strong>{{ $def['label'] ?? $s->type }}</strong> <span class="muted small">{{ \Illuminate\Support\Str::limit($s->data['heading'] ?? ($s->data['items'][0]['title'] ?? ''), 60) }}</span> @unless ($s->is_visible)<span class="tag">ascunsă</span>@endunless</span>
            <span class="btn-row">
                <form method="post" action="{{ route('admin.pages.sections.move', [$page, $s]) }}">@csrf<input type="hidden" name="dir" value="up"><button class="link-btn small" type="submit">Sus</button></form>
                <form method="post" action="{{ route('admin.pages.sections.move', [$page, $s]) }}">@csrf<input type="hidden" name="dir" value="down"><button class="link-btn small" type="submit">Jos</button></form>
            </span>
        </summary>
        <div class="sec-item__body">
            @if ($def)
            <form method="post" action="{{ route('admin.pages.sections.update', [$page, $s]) }}">
                @csrf @method('PUT')
                @foreach ($def['fields'] as $f)
                    @include('admin.fields.field', ['f' => $f, 'name' => 'data['.$f['name'].']', 'value' => old('data.'.$f['name'], $s->data[$f['name']] ?? null), 'errKey' => 'data.'.$f['name']])
                @endforeach
                @include('admin.fields.field', ['f' => ['name' => 'is_visible', 'label' => 'Secțiune vizibilă', 'type' => 'checkbox'], 'name' => 'is_visible', 'value' => $s->is_visible])
                <div class="btn-row"><button class="btn" type="submit">Salvează secțiunea</button></div>
            </form>
            @endif
            <form method="post" action="{{ route('admin.pages.sections.delete', [$page, $s]) }}" data-confirm="Ștergeți această secțiune din ciornă?" style="margin-top:1rem">@csrf @method('DELETE')<button class="btn btn--danger btn--sm" type="submit">Șterge secțiunea</button></form>
        </div>
    </details>
@empty
    <p class="muted">Pagina nu are secțiuni.@if ($page->template === 'quote') Formularul de ofertă este afișat automat.@endif</p>
@endforelse
</div>
<form class="panel" method="post" action="{{ route('admin.pages.sections.add', $page) }}" style="margin-top:1.5rem">
    @csrf
    <div class="filters"><div class="field"><label for="add-type">Adaugă secțiune</label><select class="select" id="add-type" name="type">@foreach ($types as $k => $t)<option value="{{ $k }}">{{ $t['label'] }}</option>@endforeach</select></div><button class="btn btn--line" type="submit">Adaugă</button></div>
</form>
<div class="btn-row">
    @if ($page->status === 'published' && $page->slug !== '/')<form method="post" action="{{ route('admin.pages.unpublish', $page) }}" data-confirm="Retrageți pagina de pe site?">@csrf<button class="btn btn--line btn--sm" type="submit">Retrage de pe site</button></form>@endif
    @unless ($page->is_system)<form method="post" action="{{ route('admin.pages.destroy', $page) }}" data-confirm="Ștergeți definitiv pagina?">@csrf @method('DELETE')<button class="btn btn--danger btn--sm" type="submit">Șterge pagina</button></form>@endunless
</div>
@include('admin.partials.media-picker')
@endsection
