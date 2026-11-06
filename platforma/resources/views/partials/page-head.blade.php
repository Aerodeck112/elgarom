<header class="page-head on-dark">
    <div class="wrap">
        <nav class="crumbs" aria-label="{{ __('Fir de navigare') }}">
            <ol>
                <li><a href="{{ lurl('/') }}">{{ __('Acasă') }}</a></li>
                @foreach ($crumbs ?? [] as [$label, $href])
                    <li>@if ($href)<a href="{{ $href }}">{{ $label }}</a>@else<span aria-current="page">{{ $label }}</span>@endif</li>
                @endforeach
            </ol>
        </nav>
        <h1>{{ $title }}</h1>
        @if (! empty($lead))<p class="lead">{{ $lead }}</p>@endif
    </div>
    <svg class="page-head__profile" viewBox="0 0 460 420" aria-hidden="true">
        <g fill="none" stroke="#d5dade" stroke-width="1.5">
            <path d="M40 20 h46 v318 h334 v46 H40 z"/>
            <path d="M86 338 q0 0 0 0" />
            <path d="M14 20 v364 M8 20 h12 M8 384 h12" stroke-width="1"/>
            <path d="M40 410 h380 M40 404 v12 M420 404 v12" stroke-width="1"/>
        </g>
    </svg>
</header>
