<?php

namespace App\Enums;

/**
 * Categories for the Admin Notification Center. This enum is the single source
 * of truth for the icon and color of every admin notification — call sites must
 * never hardcode icons or colors, they resolve them from the chosen category.
 */
enum AdminNotificationCategory: string
{
    case User = 'user';
    case Kyc = 'kyc';
    case Interest = 'interest';
    case Investment = 'investment';
    case Payout = 'payout';
    case News = 'news';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::User => 'المستثمرون',
            self::Kyc => 'التحقق',
            self::Interest => 'الاهتمامات',
            self::Investment => 'المشاركات',
            self::Payout => 'التوزيعات',
            self::News => 'الأخبار',
            self::System => 'النظام',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::User => 'info',
            self::Kyc => 'warning',
            self::Interest => 'primary',
            self::Investment => 'success',
            self::Payout => 'warning',
            self::News => 'info',
            self::System => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::User => 'heroicon-o-user-plus',
            self::Kyc => 'heroicon-o-shield-check',
            self::Interest => 'heroicon-o-hand-raised',
            self::Investment => 'heroicon-o-briefcase',
            self::Payout => 'heroicon-o-banknotes',
            self::News => 'heroicon-o-newspaper',
            self::System => 'heroicon-o-cog-6-tooth',
        };
    }
}
