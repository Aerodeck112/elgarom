@extends('layouts.site')
@section('content')
    @include('partials.page-head', ['title' => $project->title, 'lead' => $project->summary, 'crumbs' => [['Proiecte', url('/proiecte')], [$project->title, null]]])
    <section class="sec sec--white">
        <div class="wrap">
            @if ($project->publicClient())<p class="muted">Client: {{ $project->publicClient() }}</p>@endif
            <div class="prose">{{ md($project->body) }}</div>
        </div>
    </section>
    @include('sections.gallery', ['d' => ['heading' => 'Imagini', 'images' => $project->gallery ?? []], 'tone' => 'sec--zinc'])
    @include('sections.cta', ['d' => ['heading' => 'Aveți o piesă similară?', 'text' => 'Trimiteți-ne detaliile și vă propunem procesul potrivit.', 'button_label' => 'Solicită ofertă', 'button_url' => '/solicita-oferta']])
@endsection
