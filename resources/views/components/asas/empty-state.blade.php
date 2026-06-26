@props([
    'icon' => 'heroicon-o-inbox',
    'title' => 'لا توجد بيانات',
    'description' => null,
])

{{-- Professional empty state: icon + title + description + optional `cta` slot. --}}
<div {{ $attributes->merge(['class' => 'asas-empty']) }}>
    <span class="asas-empty__icon">@svg($icon)</span>
    <div class="asas-empty__title">{{ $title }}</div>
    @if ($description)
        <div class="asas-empty__desc">{{ $description }}</div>
    @endif
    @isset($cta)
        <div class="asas-empty__cta">{{ $cta }}</div>
    @endisset
</div>
