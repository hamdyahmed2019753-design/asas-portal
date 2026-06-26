<?php

namespace App\Enums;

enum InvestmentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * Human-readable Arabic label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد الاعتماد',
            self::Approved => 'معتمدة',
            self::Rejected => 'مرفوضة',
        };
    }

    /**
     * Filament/UI color token for the status badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
