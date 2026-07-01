<?php

namespace App\Enums;

enum WalletTransactionReason: string
{
    case CapitalReturn = 'capital_return';
    case Withdrawal = 'withdrawal';
    case WithdrawalRefund = 'withdrawal_refund';
    case Reinvestment = 'reinvestment';
    case ReinvestmentRefund = 'reinvestment_refund';

    public function label(): string
    {
        return match ($this) {
            self::CapitalReturn => 'استرداد رأس المال',
            self::Withdrawal => 'سحب',
            self::WithdrawalRefund => 'إلغاء سحب',
            self::Reinvestment => 'إعادة استثمار',
            self::ReinvestmentRefund => 'إلغاء إعادة استثمار',
        };
    }
}
