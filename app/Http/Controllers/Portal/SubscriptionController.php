<?php

namespace App\Http\Controllers\Portal;

use App\Enums\InvestmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ReceiptUploadRequest;
use App\Http\Requests\Portal\SubscribeRequest;
use App\Models\Contract;
use App\Models\Investment;
use App\Services\Portal\InvestmentPortalService;
use App\Services\Portal\SubscriptionService;
use App\Services\Portal\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Direct bank-transfer subscription: subscribe (pick shares) → transfer page →
 * upload receipt. Thin controller; ownership + KYC via the InvestmentPolicy.
 */
class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionService $service) {}

    public function store(SubscribeRequest $request, Contract $contract, InvestmentPortalService $investments, WalletService $wallet): RedirectResponse
    {
        $this->authorize('create', Investment::class); // KYC-approved investors only
        abort_unless($this->service->subscribable($contract), 404);

        // Never open a second subscription while one is still in progress/active.
        $existing = $investments->forContract($request->user(), $contract);
        if ($existing !== null && $existing->status !== InvestmentStatus::Rejected) {
            return redirect()->route(
                $existing->status === InvestmentStatus::PendingPayment ? 'portal.investments.transfer' : 'portal.investments.show',
                $existing,
            );
        }

        $shares = (int) $request->validated('shares');
        $amount = round((float) $contract->share_price * $shares, 2);

        // Reinvest from the wallet when requested and the balance covers it →
        // straight to a pending investment (no bank transfer / receipt).
        if ($request->input('method') === 'wallet' && $wallet->balance($request->user()) >= $amount) {
            $investment = $this->service->subscribeFromWallet($request->user(), $contract, $shares);

            return redirect()->route('portal.investments.show', $investment)
                ->with('status', 'تم إنشاء مشاركتك من رصيدك وهي بانتظار الاعتماد.');
        }

        $investment = $this->service->subscribe($request->user(), $contract, $shares);

        return redirect()->route('portal.investments.transfer', $investment);
    }

    public function transfer(Investment $investment): View
    {
        $this->authorize('view', $investment);
        abort_unless($investment->status === InvestmentStatus::PendingPayment, 404);

        return view('portal.investments.transfer', [
            'investment' => $investment->load('contract'),
            'banks' => $this->service->bankAccounts(),
        ]);
    }

    public function submitReceipt(ReceiptUploadRequest $request, Investment $investment): RedirectResponse
    {
        $this->authorize('view', $investment); // owner
        abort_unless($investment->status === InvestmentStatus::PendingPayment, 404);

        $this->service->submitReceipt($request->user(), $investment, $request->file('receipt'));

        return redirect()->route('portal.investments.show', $investment)
            ->with('status', 'تم استلام إيصال التحويل، وسيتم اعتماد مشاركتك بعد المراجعة.');
    }
}
