@props([
    'title' => 'النشاط الأخير',
])

{{-- Container for an activity feed (timeline items go in the slot). --}}
<div {{ $attributes->merge(['class' => 'asas-card asas-activity-card']) }}>
    <div class="asas-card__header">
        <span class="asas-card__title">{{ $title }}</span>
        @isset($action)
            <div class="asas-card__hint">{{ $action }}</div>
        @endisset
    </div>
    <div class="asas-activity-card__body">{{ $slot }}</div>
</div>
