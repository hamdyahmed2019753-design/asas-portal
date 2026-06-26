<?php

namespace App\Filament\Widgets\Base;

use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

/**
 * Base for activity-feed widgets. Concrete widgets implement getItems() to return
 * a collection of items (keys: event, title, time); this base renders them with
 * the <x-asas.timeline-item> component inside an activity card, with a built-in
 * empty state. Abstract — never registered/discovered directly.
 */
abstract class BaseActivityWidget extends Widget
{
    protected static string $view = 'filament.widgets.base.activity-widget';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $heading = 'النشاط الأخير';

    protected string $emptyTitle = 'لا يوجد نشاط بعد';

    protected string $emptyDescription = 'سيظهر هنا أحدث نشاط في النظام.';

    /**
     * @return Collection<int, array<string, mixed>>
     */
    abstract protected function getItems(): Collection;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'items' => $this->getItems(),
            'heading' => $this->heading,
            'emptyTitle' => $this->emptyTitle,
            'emptyDescription' => $this->emptyDescription,
        ];
    }
}
