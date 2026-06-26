@php($record = $getRecord())

{{-- Status badge column — colour/label come from the model's accessors (Enum),
     interpolated by the design-system component. No colour conditionals. --}}
<x-asas.status-badge :color="$record->status_color" :label="$record->status_label" />
