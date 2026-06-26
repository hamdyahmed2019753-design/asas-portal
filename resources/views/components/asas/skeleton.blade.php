@props([
    'type' => 'kpi',
    'rows' => 4,
])

{{-- Skeleton loaders. `type` only switches structure: kpi | chart | table | activity. --}}
<div {{ $attributes->merge(['class' => 'asas-skeleton asas-skeleton--' . $type]) }}>
    @if ($type === 'kpi')
        <div class="asas-card asas-kpi">
            <div class="asas-kpi__head">
                <div class="asas-skeleton__line" style="width: 90px;"></div>
                <div class="asas-skeleton__block" style="width: 40px; height: 40px; border-radius: 10px;"></div>
            </div>
            <div class="asas-skeleton__block" style="width: 72px; height: 28px; margin-top: 6px;"></div>
            <div class="asas-skeleton__line" style="width: 54px; margin-top: 8px;"></div>
        </div>
    @elseif ($type === 'chart')
        <div class="asas-card">
            <div class="asas-skeleton__line" style="width: 140px; margin-bottom: 16px;"></div>
            <div class="asas-skeleton__block" style="width: 100%; height: 200px;"></div>
        </div>
    @elseif ($type === 'table')
        <div class="asas-card">
            @for ($i = 0; $i < $rows; $i++)
                <div class="asas-skeleton__line" style="width: {{ [100, 92, 96, 88][$i % 4] }}%; margin-bottom: 14px;"></div>
            @endfor
        </div>
    @elseif ($type === 'activity')
        <div class="asas-card">
            @for ($i = 0; $i < $rows; $i++)
                <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                    <div class="asas-skeleton__block" style="width: 12px; height: 12px; border-radius: 50%;"></div>
                    <div style="flex: 1;">
                        <div class="asas-skeleton__line" style="width: 60%; margin-bottom: 6px;"></div>
                        <div class="asas-skeleton__line" style="width: 30%;"></div>
                    </div>
                </div>
            @endfor
        </div>
    @endif
</div>
