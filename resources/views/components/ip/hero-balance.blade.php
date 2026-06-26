@props([
    'title' => '',
    'value' => '—',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'ip-hero']) }}>
    <div class="ip-hero__title">{{ $title }}</div>
    <div class="ip-hero__value">{{ $value }}</div>
    @if ($description)<div class="ip-hero__desc">{{ $description }}</div>@endif
    @isset($cta)<div class="ip-hero__cta">{{ $cta }}</div>@endisset
</div>
