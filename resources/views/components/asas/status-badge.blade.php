@props([
    'color' => 'gray',
    'label' => '',
    'dot' => true,
])

{{--
    Status badge — colour comes entirely from the model's `status_color`
    accessor (an Enum token). No match/switch/if here: the token name is
    interpolated straight into the CSS custom properties.
    Usage: <x-asas.status-badge :color="$record->status_color" :label="$record->status_label" />
--}}
<span {{ $attributes->merge(['class' => 'asas-badge']) }}
      style="background: var(--asas-{{ $color }}-50); color: var(--asas-{{ $color }}-700);">
    @if ($dot)
        <span class="asas-badge__dot" style="background: var(--asas-{{ $color }}-700);"></span>
    @endif
    {{ $label }}
</span>
