@props([
    'title' => '',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'ip-section-header']) }}>
    <div>
        <h2 class="ip-section-header__title">{{ $title }}</h2>
        @if ($subtitle)<p class="ip-section-header__subtitle">{{ $subtitle }}</p>@endif
    </div>
    @isset($action)<div class="ip-section-header__action">{{ $action }}</div>@endisset
</div>
