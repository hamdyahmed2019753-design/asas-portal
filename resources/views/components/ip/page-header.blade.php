@props([
    'title' => '',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'ip-page-header']) }}>
    <div>
        <h1 class="ip-page-header__title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="ip-page-header__subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="ip-page-header__actions">{{ $actions }}</div>
    @endisset
</div>
