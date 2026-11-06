@php $bag = $errors->getBag('mesaj'); @endphp
<form class="wizard__panel" method="post" action="{{ route('message.store') }}" novalidate data-validate>
    @csrf
    <input type="hidden" name="_locale" value="{{ app()->getLocale() }}">
    <input type="hidden" name="_t" value="{{ \App\Support\FormGuard::token() }}">
    <div class="hp" aria-hidden="true"><label>{{ __('Website') }} <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
    @if (session('message_sent'))
        <div class="alert alert--ok" role="status">{{ __('Mesajul a fost înregistrat cu numărul :ref. Vă răspundem în zilele lucrătoare.', ['ref' => session('message_sent')]) }}</div>
    @endif
    @if ($bag->any())
        <div class="alert" role="alert" tabindex="-1" data-focus>{{ __('Mesajul nu a fost trimis. Corectați câmpurile marcate.') }}</div>
    @endif
    <div class="grid-2">
        <div class="field">
            <label for="m-name">{{ __('Nume') }}</label>
            <input class="input" id="m-name" name="name" autocomplete="name" required maxlength="120" value="{{ old('name') }}" @if ($bag->has('name')) aria-invalid="true" aria-describedby="m-name-e" @endif>
            @if ($bag->has('name'))<p class="err" id="m-name-e">{{ $bag->first('name') }}</p>@endif
        </div>
        <div class="field">
            <label for="m-email">{{ __('Email') }}</label>
            <input class="input" id="m-email" name="email" type="email" autocomplete="email" required maxlength="190" value="{{ old('email') }}" @if ($bag->has('email')) aria-invalid="true" aria-describedby="m-email-e" @endif>
            @if ($bag->has('email'))<p class="err" id="m-email-e">{{ $bag->first('email') }}</p>@endif
        </div>
    </div>
    <div class="field">
        <label for="m-phone">{{ __('Telefon') }} <span class="opt">{{ __('(opțional)') }}</span></label>
        <input class="input" id="m-phone" name="phone" type="tel" autocomplete="tel" maxlength="40" value="{{ old('phone') }}">
    </div>
    <div class="field">
        <label for="m-msg">{{ __('Mesaj') }}</label>
        <textarea class="textarea" id="m-msg" name="message" required minlength="10" maxlength="4000" @if ($bag->has('message')) aria-invalid="true" aria-describedby="m-msg-e" @endif>{{ old('message') }}</textarea>
        @if ($bag->has('message'))<p class="err" id="m-msg-e">{{ $bag->first('message') }}</p>@endif
    </div>
    <label class="check"><input type="checkbox" name="privacy" value="1" required @checked(old('privacy'))> <span>{{ __('Am citit') }} <a href="{{ page_url('politica-de-confidentialitate') }}">{{ __('Politica de confidențialitate') }}</a> {{ __('și sunt de acord cu prelucrarea datelor pentru a primi răspuns.') }}</span></label>
    @if ($bag->has('privacy'))<p class="err">{{ $bag->first('privacy') }}</p>@endif
    <button class="btn" type="submit">{{ __('Trimite mesajul') }}</button>
</form>
