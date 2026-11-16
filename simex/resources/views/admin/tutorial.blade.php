@extends('admin.layout')
@section('title', 'Tutorial')
@section('content')
<div class="page-head"><div><h1 class="page-title">Tutorial</h1><p class="small muted">Cum faceți o ofertă, de la cererea clientului la PDF-ul trimis.</p></div></div>
@php
    $steps = [
        ['Cererea clientului', 'Înregistrați cererea la Mai multe → Cereri de ofertă (RFQ), cu clientul, termenul și fișierele primite. Pentru un email lung, «Integrare AI → Rezumat cerere de ofertă» extrage reperele și întrebările de clarificat.', 'admin.rfqs.index', 'rfq.view'],
        ['Calculul', '«Ofertă ștanță» deschide calculatorul de ștanțe și matrițe; «Ofertă piesă» are calculatoarele de ore utilaj, laser, abkant și finisaje. Completați secțiunile numerotate de sus în jos. Câmpurile galbene/albastre sunt date de intrare; restul se calculează pe server, cu formulele din Excel-ul validat.', 'admin.calc.create:stante-matrite-fepo', 'calc.use'],
        ['Rezultatul final', 'Banda «Rezultat final» rămâne vizibilă sus: cost, adaos, preț fără TVA și prețul final (cu echivalentul în EUR la cursul din model). Fiecare secțiune arată «OK» sau numărul de avertizări.', null, null],
        ['Avertizările', 'Bara galbenă «N avertizări active — Vezi registrul» duce la Registrul de avertizări: fiecare rând spune secțiunea și ce trebuie corectat. Un calcul cu avertizări poate fi salvat, dar trebuie verificat înainte de ofertare.', null, null],
        ['Draftul', 'În «Audit final & generare ofertă» dați un titlu, alegeți clientul și apăsați «Salvează draft». Se păstrează intrările, rezultatele și tarifele din acel moment. Drafturile se redeschid din butonul «Drafturi salvate».', null, null],
        ['Oferta', '«Creează ofertă din acest calcul» completează oferta cu reperul, cantitatea și prețul din calcul. Verificați textele și termenele, apoi trimiteți-o la aprobare.', 'admin.quotes.index', 'quotes.view'],
        ['Aprobarea și PDF-ul', 'După aprobare oferta primește număr definitiv, se blochează și se poate descărca PDF (română, engleză, germană) sau Excel. Pentru modificări după emitere se face o revizie nouă; calculul folosit rămâne blocat.', null, null],
        ['Tarifele', 'Pagina «Tarife» arată foile de parametri ale fiecărui model. Doar utilizatorii cu permisiunea de modificare a tarifelor le pot schimba; fiecare schimbare devine o versiune nouă, valabilă pentru calculele viitoare.', 'admin.rates', 'calc.use'],
        ['Cursul EUR', 'Insigna EUR din antet arată cursul BNR folosit implicit în ofertele noi. Se actualizează automat în fiecare zi lucrătoare (prin cron) sau manual din meniul insignei.', null, null],
    ];
@endphp
<ol class="tut">
    @foreach ($steps as [$title, $text, $route, $perm])
        <li class="panel"><h2>{{ $title }}</h2><p class="small">{{ $text }}</p>
            @if ($route && can($perm))@php [$n, $p] = array_pad(explode(':', $route, 2), 2, null); @endphp<a class="link small" href="{{ $p ? route($n, $p) : route($n) }}">Deschide →</a>@endif</li>
    @endforeach
</ol>
<div class="panel" style="margin-top:16px"><h2>Reguli importante</h2><ul class="small" style="margin:0;padding-left:18px;display:grid;gap:4px">
    <li>Formulele și tarifele vin exclusiv din fișierele Excel validate; aplicația nu le inventează și nu le rotunjește altfel decât Excel-ul.</li>
    <li>AI-ul redactează doar texte; nu modifică cifre.</li>
    <li>Ștergerea ofertelor din registru este permisă doar administratorului și rămâne în jurnalul de audit.</li>
</ul></div>
@endsection
