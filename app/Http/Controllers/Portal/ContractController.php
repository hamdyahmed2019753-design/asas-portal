<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ContractStatus;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Services\Portal\ContractInterestService;
use App\Services\Portal\InvestmentPortalService;
use App\Services\Portal\KycService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractController extends Controller
{
    /**
     * Statuses a contract may have while still being publicly visible.
     *
     * @var array<int, string>
     */
    private const PUBLIC_STATUSES = [
        ContractStatus::Upcoming->value,
        ContractStatus::Open->value,
    ];

    public function index(Request $request): View
    {
        $query = Contract::publicVisible();

        $status = $request->query('status');
        if (is_string($status) && in_array($status, self::PUBLIC_STATUSES, true)) {
            $query->where('status', $status);
        }

        $activity = $request->query('activity_type');
        if (is_string($activity) && $activity !== '') {
            $query->where('activity_type', $activity);
        }

        return view('portal.contracts.index', [
            'contracts' => $query->latest()->paginate(12)->withQueryString(),
            'activityTypes' => Contract::publicVisible()
                ->whereNotNull('activity_type')
                ->distinct()
                ->orderBy('activity_type')
                ->pluck('activity_type'),
            'statusOptions' => [
                ContractStatus::Upcoming->value => ContractStatus::Upcoming->label(),
                ContractStatus::Open->value => ContractStatus::Open->label(),
            ],
        ]);
    }

    public function show(Request $request, KycService $kyc, ContractInterestService $interests, InvestmentPortalService $investments, string $contract): View
    {
        // Route-model binding scoped to publicVisible() — 404 for anything hidden.
        $model = Contract::publicVisible()->findOrFail($contract);

        $user = $request->user();

        return view('portal.contracts.show', [
            'contract' => $model,
            'canInvest' => $user !== null && $kyc->canInvest($user),
            'interest' => $user !== null ? $interests->forContract($user, $model) : null,
            'investment' => $user !== null ? $investments->forContract($user, $model) : null,
        ]);
    }
}
