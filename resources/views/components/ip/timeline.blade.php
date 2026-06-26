@props([])

<div {{ $attributes->merge(['class' => 'ip-timeline']) }}>
    {{ $slot }}
</div>
