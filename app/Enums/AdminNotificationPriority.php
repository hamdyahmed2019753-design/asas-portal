<?php

namespace App\Enums;

/**
 * Priority levels for admin notifications. Critical notifications are visually
 * highlighted (danger badge) so they surface above routine events.
 */
enum AdminNotificationPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'منخفضة',
            self::Medium => 'متوسطة',
            self::High => 'عالية',
            self::Critical => 'حرجة',
        };
    }

    /**
     * Filament color token used for the badge / row highlight.
     */
    public function color(): string
    {
        return match ($this) {
            self::Low => 'gray',
            self::Medium => 'info',
            self::High => 'warning',
            self::Critical => 'danger',
        };
    }

    /**
     * Badge color — aliases color() so renderers can ask for the badge token
     * explicitly without knowing the mapping.
     */
    public function badge(): string
    {
        return $this->color();
    }

    public function isCritical(): bool
    {
        return $this === self::Critical;
    }
}
