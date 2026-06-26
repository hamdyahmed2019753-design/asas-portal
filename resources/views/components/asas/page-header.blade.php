@props([
    'title' => '',
    'subtitle' => null,
])

{{-- Page header with title, optional subtitle and an `actions` slot. --}}
<div {{ $attributes->merge(['class' => 'asas-page-header']) }}>
    <div>
        <h1 class="asas-page-header__title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="asas-page-header__subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="asas-page-header__actions">{{ $actions }}</div>
    @endisset
</div>
