@props([
    'title' => '',
    'hint' => null,
    'height' => 260,
])

{{-- Container for an ApexCharts chart. The chart itself is injected via the slot. --}}
<div {{ $attributes->merge(['class' => 'asas-card asas-chart-card']) }}>
    <div class="asas-card__header">
        <span class="asas-card__title">{{ $title }}</span>
        @isset($action)
            {{ $action }}
        @elseif ($hint)
            <span class="asas-card__hint">{{ $hint }}</span>
        @endisset
    </div>
    <div class="asas-chart-card__body" style="min-height: {{ $height }}px;">{{ $slot }}</div>
</div>
