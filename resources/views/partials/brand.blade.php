{{-- Brand mark + name, sourced entirely from the Settings service (no hardcoded brand). --}}
@php($brandLogo = setting_file_url('general.site_logo'))
@php($brandName = setting('general.site_name', 'أساس'))
@if ($brandLogo)
    <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="ip-logo__img">
@else
    <span class="ip-logo__mark">{{ mb_substr($brandName, 0, 1) }}</span>
@endif
<span>{{ $brandName }}</span>
