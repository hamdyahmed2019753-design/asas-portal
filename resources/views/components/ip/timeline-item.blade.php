@props([
    'title' => '',
    'description' => null,
    'date' => null,
    'color' => 'primary',
])

{{-- color: primary | success | warning | danger | info | gray (token-bound dot). --}}
<div {{ $attributes->merge(['class' => 'ip-timeline-item']) }}>
    <div class="ip-timeline-item__rail">
        <span class="ip-timeline-item__dot" style="--dot: var(--ip-{{ $color }}-700);"></span>
        <span class="ip-timeline-item__line"></span>
    </div>
    <div>
        <div class="ip-timeline-item__title">{{ $title }}</div>
        @if ($description)<div class="ip-timeline-item__desc">{{ $description }}</div>@endif
        @if ($date)<div class="ip-timeline-item__date">{{ $date }}</div>@endif
    </div>
</div>
