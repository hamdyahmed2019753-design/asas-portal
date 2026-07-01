<?php

namespace App\Http\Controllers\Portal;

use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\WithdrawalRequest;
use App\Models\Withdrawal;
use App\Services\Portal\WalletService;
use App\Services\Portal\WithdrawalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class WithdrawalController extends Controller
{
    public function __construct(
        private readonly WithdrawalService $service,
        private readonly WalletService $wallet,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasBankAccount()) {
            return redirect()->route('portal.settings')
                ->with('status', 'أضِف حسابك البنكي أولًا لتتمكن من السحب.');
        }

        return view('portal.wallet.withdraw', [
            'user' => $user,
            'balance' => $this->wallet->balance($user),
            'withdrawals' => $user->withdrawals()->latest()->limit(10)->get(),
        ]);
    }

    public function store(WithdrawalRequest $request): RedirectResponse
    {
        $this->service->request($request->user(), (float) $request->validated('amount'));

        return redirect()->route('portal.wallet')
            ->with('status', 'تم استلام طلب السحب وسيُراجَع ويُحوَّل إلى حسابك قريبًا.');
    }

    public function receipt(Withdrawal $withdrawal): SymfonyResponse
    {
        $this->authorize('view', $withdrawal);
        abort_unless($withdrawal->status === WithdrawalStatus::Paid && filled($withdrawal->receipt_path), 404);
        abort_unless(Storage::disk('local')->exists($withdrawal->receipt_path), 404);

        $ext = pathinfo($withdrawal->receipt_path, PATHINFO_EXTENSION) ?: 'pdf';

        return Storage::disk('local')->download($withdrawal->receipt_path, "withdrawal-{$withdrawal->id}.{$ext}");
    }
}
