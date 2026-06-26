<?php

namespace App\Enums;

enum NotificationCategory: string
{
    case Kyc = 'kyc';
    case Investment = 'investment';
    case Payout = 'payout';
    case ContractInterest = 'contract_interest';
    case News = 'news';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Kyc => 'التحقق',
            self::Investment => 'المشاركات',
            self::Payout => 'التوزيعات',
            self::ContractInterest => 'طلبات الاهتمام',
            self::News => 'الأخبار',
            self::System => 'النظام',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Kyc => 'ti-id-badge-2',
            self::Investment => 'ti-briefcase',
            self::Payout => 'ti-coins',
            self::ContractInterest => 'ti-hand-finger',
            self::News => 'ti-news',
            self::System => 'ti-settings',
        };
    }
}
