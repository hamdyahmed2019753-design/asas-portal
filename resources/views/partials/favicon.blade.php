{{-- Favicon from the Settings service (rendered only when configured). --}}
@php($favicon = setting_file_url('general.favicon'))
@if ($favicon)
    <link rel="icon" href="{{ $favicon }}">
@endif
