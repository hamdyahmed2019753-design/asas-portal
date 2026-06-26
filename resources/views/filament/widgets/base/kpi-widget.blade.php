<x-filament-widgets::widget>
    <x-asas.stats-grid :cols="$cols">
        @foreach ($cards as $card)
            <x-asas.kpi-card
                :icon="$card['icon'] ?? 'heroicon-o-chart-bar'"
                :value="$card['value'] ?? null"
                :label="$card['label'] ?? ''"
                :color="$card['color'] ?? 'primary'"
                :trend="$card['trend'] ?? null"
                :trend-color="$card['trendColor'] ?? 'success'"
                :trend-icon="$card['trendIcon'] ?? 'heroicon-m-arrow-trending-up'"
                :state="$card['state'] ?? 'default'"
            />
        @endforeach
    </x-asas.stats-grid>
</x-filament-widgets::widget>
