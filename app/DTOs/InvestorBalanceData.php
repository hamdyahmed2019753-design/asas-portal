<?php

namespace App\DTOs;

use App\Models\Payout;

/**
 * Immutable snapshot of an investor's computed balances.
 */
final class InvestorBalanceData
{
    public function __construct(
        public readonly float $totalInvested,        // إجمالي رأس المال المستثمَر (المشاركات المعتمدة)
        public readonly float $totalProfitPaid,      // الأرباح المدفوعة فعليًا
        public readonly float $totalProfitExpected,  // إجمالي الأرباح المتوقعة (كل توزيعات الربح)
        public readonly float $capitalReturned,      // رأس المال المسترد (توزيعات رأس المال المدفوعة)
        public readonly ?Payout $nextPayout,         // أقرب توزيعة قادمة (scheduled/due) أو null
    ) {}
}
