<?php

namespace App\Enums;

enum PayoutStatus: string
{
    case Scheduled = 'scheduled';
    case Due = 'due';
    case Paid = 'paid';

    /**
     * Human-readable Arabic label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'مجدولة',
            self::Due => 'مستحقة',
            self::Paid => 'مدفوعة',
        };
    }

    /**
     * Filament/UI color token for the status badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Scheduled => 'gray',
            self::Due => 'warning',
            self::Paid => 'success',
        };
    }
}
