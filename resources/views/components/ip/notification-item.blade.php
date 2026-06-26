@props([
    'title' => '',
    'description' => null,
    'date' => null,
    'unread' => false,
])

<div {{ $attributes->merge(['class' => 'ip-notif ' . ($unread ? 'ip-notif--unread' : 'ip-notif--read')]) }}>
    <span class="ip-notif__dot"></span>
    <div style="flex:1;">
        <div class="ip-notif__title">{{ $title }}</div>
        @if ($description)<div class="ip-notif__desc">{{ $description }}</div>@endif
        @if ($date)<div class="ip-notif__date">{{ $date }}</div>@endif
    </div>
    @isset($action)<div class="ip-notif__cta">{{ $action }}</div>@endisset
</div>
