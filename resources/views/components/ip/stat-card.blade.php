@props([
    'icon' => 'ti-chart-line',
    'value' => '—',
    'label' => '',
    'color' => 'primary',
    'trend' => null,
    'trendDir' => 'up',
])

{{-- color: primary | success | warning | info. Presentational only. --}}
<div {{ $attributes->merge(['class' => 'ip-card ip-stat ip-stat--' . $color]) }}>
    <div class="ip-stat__top">
        <div>
            <div class="ip-stat__label">{{ $label }}</div>
            <div class="ip-stat__value">{{ $value }}</div>
        </div>
        <span class="ip-stat__icon"><i class="ti {{ $icon }}"></i></span>
    </div>
    @if ($trend !== null)
        <div class="ip-stat__trend ip-stat__trend--{{ $trendDir }}">
            <i class="ti {{ $trendDir === 'down' ? 'ti-trending-down' : 'ti-trending-up' }}"></i>
            <span>{{ $trend }}</span>
        </div>
    @endif
</div>
