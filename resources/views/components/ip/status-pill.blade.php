@props([
    'color' => 'gray',
    'label' => '',
])

{{-- color: primary | success | warning | danger | info | gray (token-bound). --}}
<span {{ $attributes->merge(['class' => 'ip-pill ip-pill--' . $color]) }}>{{ $label }}</span>
