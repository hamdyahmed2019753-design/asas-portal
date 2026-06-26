@props([
    'title' => '',
    'subtitle' => null,
])

{{-- Container only — the actual chart is injected via the slot in later phases. --}}
<div {{ $attributes->merge(['class' => 'ip-card']) }}>
    <div class="ip-card__header">
        <div>
            <div class="ip-card__title">{{ $title }}</div>
            @if ($subtitle)<div class="ip-card__subtitle">{{ $subtitle }}</div>@endif
        </div>
        @isset($action)<div>{{ $action }}</div>@endisset
    </div>
    <div class="ip-chart-card__body">
        {{ $slot->isEmpty() ? 'مساحة المخطط' : $slot }}
    </div>
</div>
