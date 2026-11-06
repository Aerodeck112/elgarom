@extends('layouts.site')
@section('content')
    @include('partials.page-head', ['title' => $ref ? __('Cererea a fost înregistrată') : __('Solicită ofertă'), 'lead' => null, 'crumbs' => [[__('Solicită ofertă'), page_url('solicita-oferta')], [__('Confirmare'), null]]])
    <section class="sec sec--white">
        <div class="wrap prose">
            @if ($ref)
                <p>{{ __('Numărul cererii dumneavoastră este:') }}</p>
                <p class="success-ref cota" data-lead-ref="{{ $kind }}">{{ $ref }}</p>
                <p>{{ __('Am salvat cererea și fișierele atașate.') }} @if ($mailQueued){{ __('Veți primi și o confirmare pe email.') }} @endif {{ __('Echipa Elgarom analizează informațiile și vă contactează pentru detalii sau cu oferta.') }}</p>
                <p>{{ __('Păstrați numărul pentru orice discuție despre această cerere.') }}</p>
                <div class="btn-row" style="margin-top:2rem"><a class="btn btn--line" href="{{ lurl('/') }}">{{ __('Înapoi la pagina principală') }}</a></div>
            @else
                <p>{{ __('Confirmarea a fost afișată deja. Dacă ați trimis o cerere, numărul ei se află în emailul de confirmare.') }}</p>
                <a class="btn" href="{{ page_url('solicita-oferta') }}">{{ __('Trimite o cerere nouă') }}</a>
            @endif
        </div>
    </section>
@endsection
