@extends('admin.layout')
@section('title', $r['label'])
@section('content')
<div class="page-title">
    <div><p><a href="{{ route('admin.res.index', [$r['key'], 'locale' => $item->locale]) }}">{{ $r['label'] }}</a></p><h1>{{ $item->exists ? 'Editare '.$r['singular'] : 'Adaugă '.$r['singular'] }}@if ($item->locale) <span class="tag">{{ \App\Support\Locales::all()[$item->locale]['name'] ?? $item->locale }}</span>@endif</h1></div>
    @if ($item->exists && ! empty($r['public']) && ($item->is_published ?? false))<a class="btn btn--line" href="{{ ($r['public'])($item) }}" target="_blank" rel="noopener">Vezi pe site</a>@endif
</div>
@if (! empty($r['intro']))<p class="muted">{{ $r['intro'] }}</p>@endif
@if ($from)<div class="alert alert--warn">Traducere în {{ \App\Support\Locales::all()[$item->locale]['name'] }} pornind de la „{{ \Illuminate\Support\Str::limit($from->title ?? $from->label ?? $from->question ?? '', 80) }}” ({{ strtoupper($from->locale) }}). Textele de mai jos sunt copiate din original – traduceți-le înainte de publicare.</div>@endif
@if ($linked && $item->exists)
    <div class="panel">
        <h2>Alte limbi</h2>
        <ul class="lang-list">
        @foreach (\App\Support\Locales::all() as $code => $l)
            @continue($code === $item->locale)
            <li><strong>{{ $l['name'] }}:</strong>
                @if (isset($translations[$code]))<a href="{{ route('admin.res.edit', [$r['key'], $translations[$code]->id]) }}">{{ \Illuminate\Support\Str::limit($translations[$code]->title ?? $translations[$code]->label ?? $translations[$code]->question ?? 'Deschide', 70) }}</a>
                    @if (isset($translations[$code]->is_published))<span class="tag {{ $translations[$code]->is_published ? 'tag--ok' : '' }}">{{ $translations[$code]->is_published ? 'publicat' : 'nepublicat' }}</span>@endif
                @else<span class="muted">nu există</span> – <a href="{{ route('admin.res.create', [$r['key'], 'locale' => $code, 'from' => $item->id]) }}">creează traducerea</a>@endif
            </li>
        @endforeach
        </ul>
    </div>
@endif
<form class="panel" method="post" enctype="multipart/form-data" action="{{ $item->exists ? route('admin.res.update', [$r['key'], $item->id]) : route('admin.res.store', $r['key']) }}">
    @csrf
    @if ($item->exists) @method('PUT') @endif
    @if (! $item->exists && $item->locale)<input type="hidden" name="locale" value="{{ $item->locale }}">@if ($item->translation_key)<input type="hidden" name="translation_key" value="{{ $item->translation_key }}">@endif @endif
    @foreach ($r['fields'] as $f)
        @include('admin.fields.field', ['f' => $f, 'name' => $f['name'], 'value' => old($f['name'], $item->{$f['name']})])
    @endforeach
    <div class="btn-row"><button class="btn" type="submit">Salvează</button><a class="btn btn--line" href="{{ route('admin.res.index', $r['key']) }}">Înapoi la listă</a></div>
</form>
@if ($item->exists)
    <form method="post" action="{{ route('admin.res.destroy', [$r['key'], $item->id]) }}" data-confirm="Ștergeți definitiv acest element?">
        @csrf @method('DELETE')
        <button class="btn btn--danger btn--sm" type="submit">Șterge</button>
    </form>
@endif
@include('admin.partials.media-picker')
@endsection
