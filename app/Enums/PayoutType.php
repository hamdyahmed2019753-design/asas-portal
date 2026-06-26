<?php

namespace App\Enums;

enum PayoutType: string
{
    case Profit = 'profit';
    case Capital = 'capital';

    /**
     * Human-readable Arabic label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Profit => 'ربح',
            self::Capital => 'رأس مال',
        };
    }

    /**
     * Filament/UI color token for the type badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Profit => 'success',
            self::Capital => 'info',
        };
    }
}
