@props([
    'icon' => 'ti-inbox',
    'title' => 'لا توجد بيانات',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'ip-empty']) }}>
    <span class="ip-empty__icon"><i class="ti {{ $icon }}"></i></span>
    <div class="ip-empty__title">{{ $title }}</div>
    @if ($description)
        <div class="ip-empty__desc">{{ $description }}</div>
    @endif
    @isset($action)
        <div class="ip-empty__cta">{{ $action }}</div>
    @endisset
</div>
