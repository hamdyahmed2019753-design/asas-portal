@props([
    'type' => 'card',
    'rows' => 4,
])

{{-- Loading skeletons. type: card | table | chart | profile | notification. --}}
<div {{ $attributes->merge(['class' => 'ip-skeleton']) }}>
    @if ($type === 'chart')
        <div class="ip-card">
            <div class="ip-skeleton__line" style="width:35%; margin-bottom:16px;"></div>
            <div class="ip-skeleton__block" style="width:100%; height:180px;"></div>
        </div>
    @elseif ($type === 'table')
        <div class="ip-card">
            @for ($i = 0; $i < $rows; $i++)
                <div class="ip-skeleton__line" style="width:{{ [100, 92, 96, 88][$i % 4] }}%; margin-bottom:16px;"></div>
            @endfor
        </div>
    @elseif ($type === 'profile')
        <div class="ip-card" style="display:flex; gap:14px; align-items:center;">
            <div class="ip-skeleton__block" style="width:56px; height:56px; border-radius:50%;"></div>
            <div style="flex:1;">
                <div class="ip-skeleton__line" style="width:40%; margin-bottom:10px;"></div>
                <div class="ip-skeleton__line" style="width:60%;"></div>
            </div>
        </div>
    @elseif ($type === 'notification')
        <div class="ip-card">
            @for ($i = 0; $i < $rows; $i++)
                <div style="display:flex; gap:12px; margin-bottom:16px;">
                    <div class="ip-skeleton__block" style="width:8px; height:8px; border-radius:50%; margin-top:6px;"></div>
                    <div style="flex:1;">
                        <div class="ip-skeleton__line" style="width:55%; margin-bottom:8px;"></div>
                        <div class="ip-skeleton__line" style="width:30%;"></div>
                    </div>
                </div>
            @endfor
        </div>
    @else
        <div class="ip-card">
            <div class="ip-skeleton__line" style="width:45%; margin-bottom:14px;"></div>
            <div class="ip-skeleton__block" style="width:100%; height:90px;"></div>
        </div>
    @endif
</div>
