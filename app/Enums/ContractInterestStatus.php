<?php

namespace App\Enums;

enum ContractInterestStatus: string
{
    case Pending = 'pending';
    case Contacted = 'contacted';
    case Converted = 'converted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد المراجعة',
            self::Contacted => 'تم التواصل',
            self::Converted => 'تم التحويل',
            self::Rejected => 'مرفوض',
        };
    }

    /**
     * UI colour token (shared admin/portal vocabulary).
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Contacted => 'info',
            self::Converted => 'success',
            self::Rejected => 'danger',
        };
    }

    /**
     * Statuses that block a duplicate interest on the same contract.
     *
     * @return array<int, string>
     */
    public static function activeValues(): array
    {
        return [self::Pending->value, self::Contacted->value];
    }
}
