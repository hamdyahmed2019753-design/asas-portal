@props([
    'title' => '',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'ip-auth-card']) }}>
    <a class="ip-logo ip-auth-card__brand" href="{{ route('home') }}">
        @include('partials.brand')
    </a>

    <div class="ip-auth-card__head">
        <h1 class="ip-auth-card__title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="ip-auth-card__subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    <div class="ip-auth-card__body">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="ip-auth-card__footer">{{ $footer }}</div>
    @endisset
</div>
