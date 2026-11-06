@extends('layouts.site')
@section('content')
    @include('partials.page-head', ['title' => $ref ? 'Cererea a fost înregistrată' : 'Solicită ofertă', 'lead' => null, 'crumbs' => [['Solicită ofertă', url('/solicita-oferta')], ['Confirmare', null]]])
    <section class="sec sec--white">
        <div class="wrap prose">
            @if ($ref)
                <p>Numărul cererii dumneavoastră este:</p>
                <p class="success-ref cota" data-lead-ref="{{ $kind }}">{{ $ref }}</p>
                <p>Am salvat cererea și fișierele atașate. @if ($mailQueued)Veți primi și o confirmare pe email.@endif Echipa Elgarom analizează informațiile și vă contactează pentru detalii sau cu oferta.</p>
                <p>Păstrați numărul pentru orice discuție despre această cerere.</p>
                <div class="btn-row" style="margin-top:2rem"><a class="btn btn--line" href="{{ url('/') }}">Înapoi la pagina principală</a></div>
            @else
                <p>Confirmarea a fost afișată deja. Dacă ați trimis o cerere, numărul ei se află în emailul de confirmare.</p>
                <a class="btn" href="{{ url('/solicita-oferta') }}">Trimite o cerere nouă</a>
            @endif
        </div>
    </section>
@endsection
