@props([
    'icon' => 'ti-file',
    'title' => '',
    'category' => null,
    'color' => 'gray',
    'date' => null,
    'size' => null,
    'url' => '#',
])

<div {{ $attributes->merge(['class' => 'ip-doc']) }}>
    <span class="ip-doc__icon ip-doc__icon--{{ $color }}"><i class="ti {{ $icon }}"></i></span>
    <div class="ip-doc__body">
        <div class="ip-doc__title">{{ $title }}</div>
        @if ($category)<x-ip.status-pill :color="$color" :label="$category" />@endif
        <div class="ip-doc__meta">
            @if ($date)<span><i class="ti ti-calendar"></i> {{ $date }}</span>@endif
            @if ($size)<span><i class="ti ti-database"></i> {{ $size }}</span>@endif
        </div>
    </div>
    <a href="{{ $url }}" class="ip-btn ip-doc__download"><i class="ti ti-download"></i> تحميل</a>
</div>
