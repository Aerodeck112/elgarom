@extends('layouts.site')
@section('content')
    @include('partials.page-head', ['title' => $project->title, 'lead' => $project->summary, 'crumbs' => [[__('Proiecte'), page_url('proiecte')], [$project->title, null]]])
    <section class="sec sec--white">
        <div class="wrap">
            @if ($project->publicClient())<p class="muted">{{ __('Client') }}: {{ $project->publicClient() }}</p>@endif
            <div class="prose">{{ md($project->body) }}</div>
        </div>
    </section>
    @include('sections.gallery', ['d' => ['heading' => __('Imagini'), 'images' => $project->gallery ?? []], 'tone' => 'sec--zinc'])
    @include('sections.cta', ['d' => ['heading' => __('Aveți o piesă similară?'), 'text' => __('Trimiteți-ne detaliile și vă propunem procesul potrivit.'), 'button_label' => __('Solicită ofertă'), 'button_url' => page_url('solicita-oferta')]])
@endsection
