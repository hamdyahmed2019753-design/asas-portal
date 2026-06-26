@props([
    'title' => '',
])

{{-- Section header with title and an optional `action` slot. --}}
<div {{ $attributes->merge(['class' => 'asas-section-header']) }}>
    <h2 class="asas-section-header__title">{{ $title }}</h2>
    @isset($action)
        <div class="asas-section-header__action">{{ $action }}</div>
    @endisset
</div>
