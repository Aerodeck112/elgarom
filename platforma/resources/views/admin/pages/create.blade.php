@extends('admin.layout')
@section('title', 'Pagină nouă')
@section('content')
<h1>Pagină nouă <span class="tag">{{ \App\Support\Locales::all()[$loc]['name'] }}</span></h1>
<form class="panel" method="post" action="{{ route('admin.pages.store') }}">
    @csrf
    <input type="hidden" name="locale" value="{{ $loc }}">
    <div class="field"><label for="title">Titlu</label><input class="input" id="title" name="title" required maxlength="160" value="{{ old('title') }}"></div>
    <div class="field"><label for="slug">Adresă URL</label><input class="input" id="slug" name="slug" maxlength="120" value="{{ old('slug') }}" data-slug-from="title"><p class="hint">Ex. „zincare-pentru-industria-auto”. Se generează din titlu.</p></div>
    <div class="field"><label for="template">Tip pagină</label><select class="select" id="template" name="template">@foreach ($templates as $k => $l)@if ($k !== 'home')<option value="{{ $k }}" @selected(old('template', 'standard') === $k)>{{ $l }}</option>@endif @endforeach</select></div>
    <button class="btn" type="submit">Creează ciorna</button>
</form>
@endsection
