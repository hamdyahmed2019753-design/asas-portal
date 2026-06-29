<?php

namespace App\Services\Portal;

use App\Models\Payout;
use App\Models\User;
use App\Services\InvestorBalance;
use Illuminate\Support\Collection;

/**
 * Builds everything the investor dashboard (/portal) needs. Every query is scoped
 * to the given (authenticated) user via their relationships — no user_id input,
 * no global filtered queries.
 */
class InvestorDashboardService
{
    public function __construct(
        private readonly InvestorBalance $balance,
        private readonly KycService $kyc,
        private readonly ContractInterestService $interests,
        private readonly AccountSecurityService $security,
        private readonly NextStepService $nextStep,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function for(User $user): array
    {
        $balance = $this->balance->for($user);

        $approvedInvestmentIds = $user->investments()->approved()->pluck('id');

        $investmentsCount = $user->investments()->count();
        $activeCount = (int) $approvedInvestmentIds->count();

        // Reuse the already-computed next payout; just eager-load its relations.
        $nextPayout = $balance->nextPayout;
        $nextPayout?->load('investment.contract');

        $latestInvestments = $user->investments()
            ->with('contract')
            ->latest()
            ->limit(5)
            ->get();

        return [
            // The single highest-priority next action (pure — no extra query).
            'nextStep' => $this->nextStep->resolve($user, $investmentsCount > 0),
            'onboardingComplete' => $user->hasCompletedOnboarding(),
            // KYC progress widget: shown once onboarding is done but not yet approved.
            'kyc' => $user->hasCompletedOnboarding() && ! $user->kycApproved()
                ? $this->kyc->card($user)
                : null,
            'pendingInterests' => $this->interests->pendingCount($user),
            'documents' => $this->documentsSummary($user),
            'security' => $this->security->securityScore($user),
            'hasInvestments' => $investmentsCount > 0,
            'portfolioValue' => $balance->totalInvested + $balance->totalProfitPaid,
            'totalInvested' => $balance->totalInvested,
            'profitPaid' => $balance->totalProfitPaid,
            'profitExpected' => $balance->totalProfitExpected,
            'investmentsCount' => $investmentsCount,
            'activeCount' => $activeCount,
            'nextPayout' => $nextPayout,
            'latestInvestments' => $latestInvestments,
            'growth' => $this->profitGrowth($approvedInvestmentIds),
        ];
    }

    /**
     * Documents quick-access summary for the dashboard card.
     *
     * @return array{total: int, lastTitle: ?string, lastDate: ?string}
     */
    private function documentsSummary(User $user): array
    {
        $last = $user->documents()->latest()->first(['id', 'title', 'created_at']);

        return [
            'total' => $user->documents()->count(),
            'lastTitle' => $last?->title,
            'lastDate' => $last?->created_at?->format('Y-m-d'),
        ];
    }

    /**
     * Cumulative paid profit at the end of each of the last N months.
     *
     * Optimised to a SINGLE grouped aggregate query (paid-profit per calendar
     * month), then the running cumulative total is computed in PHP — replacing
     * the previous N-query monthly loop. The returned shape and numbers are
     * identical to before.
     *
     * @param  Collection<int, int>  $approvedInvestmentIds
     * @return array{labels: array<int, string>, data: array<int, float>}
     */
    private function profitGrowth(Collection $approvedInvestmentIds, int $months = 12): array
    {
        $monthExpr = (new Payout)->getConnection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', paid_at)"
            : "DATE_FORMAT(paid_at, '%Y-%m')";

        $monthly = Payout::query()
            ->whereIn('investment_id', $approvedInvestmentIds)
            ->profit()
            ->paid()
            ->whereNotNull('paid_at')
            ->selectRaw("{$monthExpr} as ym, SUM(amount) as total")
            ->groupBy('ym')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->ym => (float) $row->total]);

        $labels = [];
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $end = now()->subMonths($i)->endOfMonth();
            $labels[] = $end->translatedFormat('M');
            $key = $end->format('Y-m');
            // Cumulative paid profit up to and including this month.
            $data[] = round($monthly->filter(fn (float $total, string $ym) => $ym <= $key)->sum(), 2);
        }

        return ['labels' => $labels, 'data' => $data];
    }
}
