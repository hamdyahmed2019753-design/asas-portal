@props([
    'cols' => null,
])

{{-- Responsive stats grid. Pass :cols="3" or :cols="4" to force a column count. --}}
@php($modifier = in_array($cols, [3, 4], true) ? ' asas-stats-grid--' . $cols : '')
<div {{ $attributes->merge(['class' => 'asas-stats-grid' . $modifier]) }}>
    {{ $slot }}
</div>
