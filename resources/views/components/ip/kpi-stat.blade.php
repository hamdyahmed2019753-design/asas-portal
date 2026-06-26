@props([
    'label' => '',
    'value' => '—',
    'icon' => 'ti-chart-line',
])

{{-- Presentational KPI stat (Phase 4.1 skeleton — values are passed in, no logic). --}}
<div {{ $attributes->merge(['class' => 'ip-card']) }}>
    <div class="ip-kpi">
        <div>
            <div class="ip-kpi__label">{{ $label }}</div>
            <div class="ip-kpi__value">{{ $value }}</div>
        </div>
        <span class="ip-kpi__icon"><i class="ti {{ $icon }}"></i></span>
    </div>
</div>
