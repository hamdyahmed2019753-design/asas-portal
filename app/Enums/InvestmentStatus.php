<?php

namespace App\Enums;

enum InvestmentStatus: string
{
    case PendingPayment = 'pending_payment';
    case PaymentSubmitted = 'payment_submitted';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * Human-readable Arabic label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'بانتظار التحويل',
            self::PaymentSubmitted => 'قيد مراجعة الدفع',
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
            self::PendingPayment => 'gray',
            self::PaymentSubmitted => 'info',
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }

    /**
     * Statuses that still await admin action before the investment is active.
     *
     * @return array<int, string>
     */
    public static function awaitingApproval(): array
    {
        return [self::PaymentSubmitted->value, self::Pending->value];
    }
}
