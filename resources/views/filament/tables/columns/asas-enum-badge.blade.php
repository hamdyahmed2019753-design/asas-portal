@php($state = $getState())

{{-- Generic badge column for any enum-cast attribute exposing label()/color()
     (e.g. kyc_status). Colour comes straight from the enum — no conditionals. --}}
@if ($state)
    <x-asas.status-badge :color="$state->color()" :label="$state->label()" />
@endif
