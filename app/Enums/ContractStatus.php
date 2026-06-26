<?php

namespace App\Enums;

enum ContractStatus: string
{
    case Upcoming = 'upcoming';
    case Open = 'open';
    case Running = 'running';
    case Closed = 'closed';
    case Finished = 'finished';

    /**
     * Human-readable Arabic label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'قريبًا',
            self::Open => 'مفتوح للمشاركة',
            self::Running => 'جارٍ التنفيذ',
            self::Closed => 'مغلق',
            self::Finished => 'منتهٍ',
        };
    }

    /**
     * Filament/UI color token for the status badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Upcoming => 'info',
            self::Open => 'success',
            self::Running => 'primary',
            self::Closed => 'warning',
            self::Finished => 'gray',
        };
    }
}
