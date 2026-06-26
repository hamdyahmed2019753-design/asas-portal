@props([
    'amount' => '—',
    'dueDate' => null,
    'type' => null,
    'statusLabel' => null,
    'statusColor' => 'gray',
])

<div {{ $attributes->merge(['class' => 'ip-payout']) }}>
    <div>
        <div class="ip-payout__amount">{{ $amount }}</div>
        @if ($dueDate)<div class="ip-payout__meta">تاريخ الاستحقاق: {{ $dueDate }}</div>@endif
    </div>
    <div class="ip-payout__side">
        @if ($type)<span class="ip-chip">{{ $type }}</span>@endif
        @if ($statusLabel)<x-ip.status-pill :color="$statusColor" :label="$statusLabel" />@endif
    </div>
</div>
