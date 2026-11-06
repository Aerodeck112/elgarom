@extends('layouts.site')
@section('content')
    @include('partials.page-head', ['title' => $data['title'], 'lead' => $data['lead'] ?? null, 'crumbs' => $crumbs])
    @if ($page->template === 'quote')
        @include('pages.partials.quote')
    @endif
    @include('partials.sections', ['sections' => $data['sections'] ?? [], 'legal' => $page->template === 'legal'])
@endsection
