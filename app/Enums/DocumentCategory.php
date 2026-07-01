<?php

namespace App\Enums;

enum DocumentCategory: string
{
    case Contract = 'contract';
    case Kyc = 'kyc';
    case PaymentReceipt = 'payment_receipt';
    case PayoutStatement = 'payout_statement';
    case PortfolioStatement = 'portfolio_statement';
    case Tax = 'tax';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Contract => 'العقود',
            self::Kyc => 'مستندات التحقق',
            self::PaymentReceipt => 'إيصالات الدفع',
            self::PayoutStatement => 'كشوف التوزيعات',
            self::PortfolioStatement => 'كشوف المحفظة',
            self::Tax => 'المستندات الضريبية',
            self::System => 'مستندات النظام',
        };
    }

    /**
     * Portal --ip-* colour token.
     */
    public function color(): string
    {
        return match ($this) {
            self::Contract => 'primary',
            self::Kyc => 'info',
            self::PaymentReceipt => 'success',
            self::PayoutStatement => 'success',
            self::PortfolioStatement => 'primary',
            self::Tax => 'warning',
            self::System => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Contract => 'ti-file-text',
            self::Kyc => 'ti-id-badge-2',
            self::PaymentReceipt => 'ti-receipt-2',
            self::PayoutStatement => 'ti-receipt',
            self::PortfolioStatement => 'ti-chart-pie',
            self::Tax => 'ti-file-invoice',
            self::System => 'ti-settings',
        };
    }
}
