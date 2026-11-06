@php
    $bag = $errors->getBag('oferta');
    $stash = session('quote_stash', []);
    $maxFiles = config('elgarom.uploads.quote_max_files');
    $maxMb = (int) round(config('elgarom.uploads.quote_max_kb') / 1024);
    $exts = config('elgarom.uploads.quote_extensions');
    $stepOf = ['name' => 1, 'company' => 1, 'email' => 1, 'phone' => 1, 'city' => 1, 'part_type' => 2, 'material' => 2, 'process' => 2, 'description' => 2,
        'quantity' => 3, 'weight' => 3, 'dimensions' => 3, 'deadline' => 3, 'deadline_note' => 3, 'files' => 4, 'files.*' => 4, 'privacy' => 5];
    $errStep = 1;
    $startStep = 1;
    foreach (array_keys($bag->messages()) as $k) { $errStep = $stepOf[preg_replace('/\.\d+$/', '.*', $k)] ?? 5; break; }
    $startStep = $bag->any() ? $errStep : 1;
    $materials = ['Oțel carbon cu conținut scăzut de carbon', 'Oțel carbon cu conținut mediu de carbon', 'Oțel carbon cu conținut ridicat de carbon', 'Oțel inoxidabil', 'Oțel aliat / înalt aliat', 'Nu știu'];
    $e = fn ($f) => $bag->has($f) ? 'aria-invalid=true aria-describedby='.str_replace('.', '-', $f).'-e' : '';
    $err = fn ($f) => $bag->has($f) ? '<p class="err" id="'.e(str_replace('.', '-', $f)).'-e">'.e($bag->first($f)).'</p>' : '';
@endphp
<section class="sec sec--zinc" id="formular">
    <div class="wrap wizard">
        <form class="wizard__panel" method="post" action="{{ route('quote.store') }}" enctype="multipart/form-data" novalidate
              data-wizard data-start-step="{{ $bag->any() ? $errStep : 1 }}" data-max-files="{{ $maxFiles }}" data-max-bytes="{{ config('elgarom.uploads.quote_max_kb') * 1024 }}" data-exts="{{ implode(',', $exts) }}">
            @csrf
            <input type="hidden" name="_t" value="{{ \App\Support\FormGuard::token() }}">
            <input type="hidden" name="source" value="{{ old('source', 'formular') }}" data-source>
            <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

            <ol class="wizard__steps" data-wizard-progress aria-label="Pașii formularului">
                <li>Contact</li><li>Piesa</li><li>Cantitate</li><li>Fișiere</li><li>Trimitere</li>
            </ol>

            @if ($bag->any())
                <div class="alert" role="alert" tabindex="-1" data-focus>
                    <strong>Cererea nu a fost trimisă.</strong> Verificați câmpurile marcate. Datele completate au fost păstrate.
                    <ul>@foreach ($bag->all() as $m)<li>{{ $m }}</li>@endforeach</ul>
                </div>
            @endif
            @if (session('quote_failed'))
                <div class="alert" role="alert" tabindex="-1" data-focus>{{ session('quote_failed') }}</div>
            @endif

            <fieldset class="wizard__step @if ($startStep === 1) is-active @endif" data-step="1" style="border:0;padding:0;margin:0">
                <legend class="sr-only">Pasul 1 din 5: date de contact</legend>
                <h2>Cum vă contactăm</h2>
                <div class="grid-2">
                    <div class="field"><label for="q-name">Nume și prenume</label><input class="input" id="q-name" name="name" autocomplete="name" required maxlength="120" value="{{ old('name') }}" {!! $e('name') !!}>{!! $err('name') !!}</div>
                    <div class="field"><label for="q-company">Companie <span class="opt">(opțional)</span></label><input class="input" id="q-company" name="company" autocomplete="organization" maxlength="160" value="{{ old('company') }}"></div>
                    <div class="field"><label for="q-email">Email</label><input class="input" id="q-email" name="email" type="email" autocomplete="email" required maxlength="190" value="{{ old('email') }}" {!! $e('email') !!}>{!! $err('email') !!}</div>
                    <div class="field"><label for="q-phone">Telefon <span class="opt">(opțional)</span></label><input class="input" id="q-phone" name="phone" type="tel" autocomplete="tel" maxlength="40" value="{{ old('phone') }}" {!! $e('phone') !!}>{!! $err('phone') !!}</div>
                </div>
                <div class="field"><label for="q-city">Localitate <span class="opt">(opțional)</span></label><input class="input" id="q-city" name="city" autocomplete="address-level2" maxlength="120" value="{{ old('city') }}"></div>
            </fieldset>

            <fieldset class="wizard__step @if ($startStep === 2) is-active @endif" data-step="2" style="border:0;padding:0;margin:0">
                <legend class="sr-only">Pasul 2 din 5: piesa</legend>
                <h2>Ce piese doriți să zincați</h2>
                <p class="field" style="margin-bottom:.5rem"><span style="font-weight:620;font-size:var(--t-sm)">Tipul pieselor</span></p>
                <div class="choices" role="radiogroup" aria-label="Tipul pieselor">
                    @foreach (\App\Models\QuoteRequest::PART_TYPES as $k => $label)
                        <label class="choice"><input type="radio" name="part_type" value="{{ $k }}" @checked(old('part_type', 'altele') === $k)><span>{{ $label }}</span></label>
                    @endforeach
                </div>
                <div class="grid-2">
                    <div class="field"><label for="q-material">Material <span class="opt">(opțional)</span></label>
                        <select class="select" id="q-material" name="material">
                            <option value="">Alegeți</option>
                            @foreach ($materials as $mat)<option value="{{ $mat }}" @selected(old('material') === $mat)>{{ $mat }}</option>@endforeach
                        </select>
                    </div>
                    <div class="field"><label for="q-process">Mod de execuție</label>
                        <select class="select" id="q-process" name="process">
                            @foreach (\App\Models\QuoteRequest::PROCESSES as $k => $label)<option value="{{ $k }}" @selected(old('process', 'nu_stiu') === $k)>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="field">
                    <label for="q-desc">Descrierea piesei și a cerințelor</label>
                    <p class="hint" id="q-desc-h">De exemplu: ce este piesa, unde va fi folosită, cerințe de aspect sau de protecție, standarde cerute.</p>
                    <textarea class="textarea" id="q-desc" name="description" required minlength="10" maxlength="6000" aria-describedby="q-desc-h" {!! $e('description') !!}>{{ old('description') }}</textarea>{!! $err('description') !!}
                </div>
            </fieldset>

            <fieldset class="wizard__step @if ($startStep === 3) is-active @endif" data-step="3" style="border:0;padding:0;margin:0">
                <legend class="sr-only">Pasul 3 din 5: cantitate, dimensiuni și termen</legend>
                <h2>Cantitate, dimensiuni, termen</h2>
                <p class="muted small">Toate câmpurile sunt opționale. Completați ce știți – le clarificăm împreună.</p>
                <div class="grid-2">
                    <div class="field"><label for="q-qty">Cantitate</label><input class="input" id="q-qty" name="quantity" maxlength="60" placeholder="ex. 5.000 buc./lună" value="{{ old('quantity') }}"></div>
                    <div class="field"><label for="q-weight">Greutate</label><input class="input" id="q-weight" name="weight" maxlength="60" placeholder="ex. 0,2 kg/buc. sau 300 kg/lot" value="{{ old('weight') }}"></div>
                </div>
                <div class="field"><label for="q-dims">Dimensiuni maxime ale piesei</label><input class="input" id="q-dims" name="dimensions" maxlength="120" placeholder="ex. 600 × 300 × 80 mm" value="{{ old('dimensions') }}"></div>
                <div class="grid-2">
                    <div class="field"><label for="q-deadline">Termen dorit</label><input class="input" id="q-deadline" name="deadline" type="date" min="{{ now()->toDateString() }}" value="{{ old('deadline') }}" {!! $e('deadline') !!}>{!! $err('deadline') !!}</div>
                    <div class="field"><label for="q-dnote">Detalii termen</label><input class="input" id="q-dnote" name="deadline_note" maxlength="120" placeholder="ex. livrări lunare" value="{{ old('deadline_note') }}"></div>
                </div>
            </fieldset>

            <fieldset class="wizard__step @if ($startStep === 4) is-active @endif" data-step="4" style="border:0;padding:0;margin:0">
                <legend class="sr-only">Pasul 4 din 5: fișiere</legend>
                <h2>Desene și fotografii</h2>
                <p class="muted small">Opțional. Până la {{ $maxFiles }} fișiere de maximum {{ $maxMb }} MB fiecare: {{ implode(', ', array_map('strtoupper', $exts)) }}. Fișierele sunt private și accesibile doar echipei Elgarom.</p>
                @if ($stash)
                    <div class="alert alert--ok" role="status">Fișierele încărcate anterior au fost păstrate:
                        <ul>@foreach ($stash as $f)<li>{{ $f['name'] }} ({{ number_format($f['size'] / 1048576, 1, ',', '') }} MB)</li>@endforeach</ul>
                    </div>
                @endif
                <div class="field">
                    <label class="drop" for="q-files" data-drop>
                        <strong>Alegeți fișierele</strong> sau trageți-le aici
                    </label>
                    <input class="sr-only" id="q-files" name="files[]" type="file" multiple accept="{{ implode(',', array_map(fn ($x) => '.'.$x, $exts)) }}" data-files>
                    <ul class="files" data-file-list aria-live="polite"></ul>
                    {!! $err('files') !!}
                    @foreach (\Illuminate\Support\Arr::flatten($bag->get('files.*')) as $m)<p class="err">{{ $m }}</p>@endforeach
                </div>
            </fieldset>

            <fieldset class="wizard__step @if ($startStep === 5) is-active @endif" data-step="5" style="border:0;padding:0;margin:0">
                <legend class="sr-only">Pasul 5 din 5: revizuire și trimitere</legend>
                <h2>Verificați și trimiteți</h2>
                <dl class="review" data-review></dl>
                <label class="check"><input type="checkbox" name="privacy" value="1" required @checked(old('privacy')) {!! $e('privacy') !!}> <span>Am citit <a href="{{ url('/politica-de-confidentialitate') }}" target="_blank">Politica de confidențialitate</a> și sunt de acord cu prelucrarea datelor pentru pregătirea ofertei.</span></label>
                {!! $err('privacy') !!}
                <label class="check"><input type="checkbox" name="marketing" value="1" @checked(old('marketing'))> <span>Opțional: doresc să primesc ocazional informări comerciale de la Elgarom. Mă pot dezabona oricând.</span></label>
                <button class="btn" type="submit" data-submit>Trimite cererea de ofertă</button>
            </fieldset>

            <div class="wizard__nav" data-wizard-nav>
                <button class="btn btn--line" type="button" data-prev>Înapoi</button>
                <button class="btn" type="button" data-next>Continuă</button>
            </div>
        </form>

        <aside class="wizard__aside">
            <h2 style="font-size:var(--t-lg)">Ce urmează</h2>
            <ol class="steps" style="margin-bottom:2rem">
                <li class="step" style="grid-template-columns:3rem 1fr;padding:1rem 0"><div><h3 style="font-size:var(--t-base)">Primiți un număr de cerere</h3><p class="small">Imediat după trimitere, pe ecran și pe email.</p></div></li>
                <li class="step" style="grid-template-columns:3rem 1fr;padding:1rem 0"><div><h3 style="font-size:var(--t-base)">Echipa tehnică analizează piesa</h3><p class="small">Vă contactăm dacă avem nevoie de detalii.</p></div></li>
                <li class="step" style="grid-template-columns:3rem 1fr;padding:1rem 0"><div><h3 style="font-size:var(--t-base)">Primiți oferta</h3><p class="small">Cu procesul propus pentru piesa dumneavoastră.</p></div></li>
            </ol>
            <p class="small muted">Preferați telefonul?</p>
            <dl class="contact__list">
                @foreach (setting('company.phones', []) as $p)
                    <div><dt>{{ $p['label'] }}</dt><dd><a href="{{ tel_href($p['number']) }}" data-track="contact_click" data-track-label="telefon">{{ $p['number'] }}</a></dd></div>
                @endforeach
            </dl>
        </aside>
    </div>
</section>
