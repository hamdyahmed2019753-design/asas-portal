<?php

namespace App\Enums;

use App\Filament\Resources\ContractInterestResource;
use App\Filament\Resources\InvestmentResource;
use App\Filament\Resources\InvestorResource;
use App\Filament\Resources\NewsResource;
use App\Filament\Resources\PayoutResource;

/**
 * Categories for the Admin Notification Center. This enum is the single source
 * of truth for the icon, color, and click-through destination of every admin
 * notification — call sites must never hardcode these, they resolve from the
 * chosen category.
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

    /**
     * Click-through destination: ALWAYS the relevant list (index) page, never a
     * single record — so opening a notification lands on all items of its kind.
     * Returns null for System (no associated list).
     */
    public function url(): ?string
    {
        return match ($this) {
            self::User, self::Kyc => InvestorResource::getUrl('index'),
            self::Interest => ContractInterestResource::getUrl('index'),
            self::Investment => InvestmentResource::getUrl('index'),
            self::Payout => PayoutResource::getUrl('index'),
            self::News => NewsResource::getUrl('index'),
            self::System => null,
        };
    }

    /**
     * Resolve the list URL from a stored notification payload (its 'category').
     * Stale rows that captured a record URL are therefore corrected at render.
     *
     * @param  array<string, mixed>  $data
     */
    public static function urlFor(array $data): ?string
    {
        return self::tryFrom((string) ($data['category'] ?? ''))?->url();
    }
}
