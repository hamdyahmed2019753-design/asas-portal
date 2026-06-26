@props([
    'title' => null,
    'subtitle' => null,
    'feature' => false,
])

<div {{ $attributes->merge(['class' => 'ip-card' . ($feature ? ' ip-card--feature' : '')]) }}>
    @if ($title || isset($action))
        <div class="ip-card__header">
            <div>
                @if ($title)<div class="ip-card__title">{{ $title }}</div>@endif
                @if ($subtitle)<div class="ip-card__subtitle">{{ $subtitle }}</div>@endif
            </div>
            @isset($action)<div>{{ $action }}</div>@endisset
        </div>
    @endif
    {{ $slot }}
</div>
