<x-filament-widgets::widget>
    <x-asas.activity-card :title="$heading">
        @forelse ($items as $item)
            <x-asas.timeline-item
                :event="$item['event'] ?? 'updated'"
                :title="$item['title'] ?? ''"
                :time="$item['time'] ?? null"
            />
        @empty
            <x-asas.empty-state
                icon="heroicon-o-clock"
                :title="$emptyTitle"
                :description="$emptyDescription"
            />
        @endforelse
    </x-asas.activity-card>
</x-filament-widgets::widget>
