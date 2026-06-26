<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\OnboardingDocumentsRequest;
use App\Services\Portal\KycService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KycController extends Controller
{
    /**
     * Show the resubmission form (only available to rejected investors).
     */
    public function resubmitForm(Request $request): View
    {
        $this->authorize('resubmitKyc', $request->user());

        return view('portal.kyc.resubmit');
    }

    /**
     * Handle a fresh document upload after a rejection.
     */
    public function resubmit(OnboardingDocumentsRequest $request, KycService $service): RedirectResponse
    {
        $this->authorize('resubmitKyc', $request->user());

        $service->resubmit($request->user(), [
            'identity' => $request->file('identity'),
            'iban' => $request->file('iban'),
            'address' => $request->file('address'),
        ]);

        return redirect()
            ->route('portal.profile')
            ->with('status', 'تم إعادة رفع مستنداتك بنجاح، وستتم مراجعتها قريبًا.');
    }
}
