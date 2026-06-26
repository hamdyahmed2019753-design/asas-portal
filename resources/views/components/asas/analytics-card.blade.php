@props([
    'title' => '',
    'hint' => null,
])

{{-- Container for analytics content (numbers + small visuals). --}}
<div {{ $attributes->merge(['class' => 'asas-card asas-analytics-card']) }}>
    <div class="asas-card__header">
        <span class="asas-card__title">{{ $title }}</span>
        @isset($action)
            {{ $action }}
        @elseif ($hint)
            <span class="asas-card__hint">{{ $hint }}</span>
        @endisset
    </div>
    <div class="asas-analytics-card__body">{{ $slot }}</div>
</div>
