@props([
    'title' => '',
    'activity' => null,
    'targetAmount' => null,
    'expectedReturn' => null,
    'duration' => null,
    'statusLabel' => null,
    'statusColor' => 'info',
])

{{-- Placeholder presentational contract card (Phase 4.2 — values passed in). --}}
<div {{ $attributes->merge(['class' => 'ip-card ip-contract']) }}>
    <div class="ip-contract__head">
        <div>
            <div class="ip-contract__title">{{ $title }}</div>
            @if ($activity)<div class="ip-contract__activity">{{ $activity }}</div>@endif
        </div>
        @if ($statusLabel)<x-ip.status-pill :color="$statusColor" :label="$statusLabel" />@endif
    </div>

    <div class="ip-contract__grid">
        <div>
            <div class="ip-contract__meta-label">النصاب المستهدف</div>
            <div class="ip-contract__meta-value">{{ $targetAmount ?? '—' }}</div>
        </div>
        <div>
            <div class="ip-contract__meta-label">العائد المتوقع</div>
            <div class="ip-contract__meta-value">{{ $expectedReturn ?? '—' }}</div>
        </div>
        <div>
            <div class="ip-contract__meta-label">المدة</div>
            <div class="ip-contract__meta-value">{{ $duration ?? '—' }}</div>
        </div>
    </div>

    @isset($cta)<div>{{ $cta }}</div>@endisset
</div>
