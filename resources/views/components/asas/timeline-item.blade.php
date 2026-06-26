@props([
    'event' => 'updated',
    'title' => '',
    'time' => null,
])

{{--
    Timeline item. The dot colour is the activity-event token
    (--asas-activity-{event}) — created / updated / deleted are defined in the
    theme; new event tokens can be added there without touching this component.
    No colour conditionals here.
--}}
<div {{ $attributes->merge(['class' => 'asas-timeline-item']) }}>
    <div class="asas-timeline-item__rail">
        <span class="asas-timeline-item__dot" style="--dot: var(--asas-activity-{{ $event }});"></span>
        <span class="asas-timeline-item__line"></span>
    </div>
    <div class="asas-timeline-item__content">
        <div class="asas-timeline-item__title">{{ $title }}</div>
        @if ($time)
            <div class="asas-timeline-item__time">{{ $time }}</div>
        @endif
        @if (trim($slot))
            <div class="asas-timeline-item__body">{{ $slot }}</div>
        @endif
    </div>
</div>
