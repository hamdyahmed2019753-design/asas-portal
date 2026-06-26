@props([
    'icon' => 'heroicon-o-chart-bar',
    'value' => null,
    'label' => '',
    'color' => 'primary',
    'trend' => null,
    'trendColor' => 'success',
    'trendIcon' => 'heroicon-m-arrow-trending-up',
    'state' => 'default',
    'emptyText' => 'لا توجد بيانات',
])

{{--
    Reusable KPI card. Colour tokens are interpolated (no colour conditionals).
    The `state` prop only switches structural layout: default | loading | empty.
    Optional sparkline area chart goes in the `spark` slot.
--}}
<div {{ $attributes->merge(['class' => 'asas-card asas-kpi']) }}>
    @if ($state === 'loading')
        <div class="asas-kpi__head">
            <div class="asas-skeleton__line" style="width: 90px;"></div>
            <div class="asas-skeleton__block" style="width: 40px; height: 40px; border-radius: 10px;"></div>
        </div>
        <div class="asas-skeleton__block" style="width: 72px; height: 28px; margin-top: 6px;"></div>
        <div class="asas-skeleton__line" style="width: 54px; margin-top: 8px;"></div>
    @elseif ($state === 'empty')
        <div class="asas-kpi__head">
            <span class="asas-kpi__label">{{ $label }}</span>
            <span class="asas-kpi__chip" style="--chip-bg: var(--asas-gray-100); --chip-fg: var(--asas-text-muted);">@svg($icon)</span>
        </div>
        <div class="asas-kpi__value" style="color: var(--asas-text-muted);">—</div>
        <div class="asas-kpi__label">{{ $emptyText }}</div>
    @else
        <div class="asas-kpi__head">
            <span class="asas-kpi__label">{{ $label }}</span>
            <span class="asas-kpi__chip" style="--chip-bg: var(--asas-{{ $color }}-50); --chip-fg: var(--asas-{{ $color }}-700);">@svg($icon)</span>
        </div>
        <div class="asas-kpi__value">{{ $value }}</div>
        @if ($trend !== null)
            <div class="asas-kpi__trend" style="--trend-fg: var(--asas-{{ $trendColor }}-700);">
                @svg($trendIcon)
                <span>{{ $trend }}</span>
            </div>
        @endif
        @isset($spark)
            <div class="asas-kpi__spark">{{ $spark }}</div>
        @endisset
    @endif
</div>
