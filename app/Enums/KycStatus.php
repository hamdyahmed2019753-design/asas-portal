<?php

namespace App\Enums;

enum KycStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';

    /**
     * Human-readable Arabic label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد المراجعة',
            self::Verified => 'موثّق',
            self::Rejected => 'مرفوض',
        };
    }

    /**
     * Filament/UI color token for the status badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Verified => 'success',
            self::Rejected => 'danger',
        };
    }
}
