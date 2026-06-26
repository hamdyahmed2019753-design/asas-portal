<?php

namespace App\Http\Middleware;

use App\Services\Portal\KycService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates investment-related routes (contract interest, investment requests, and
 * any future investment workflow) behind an approved KYC. Unapproved users are
 * redirected to their profile with an explanatory message.
 */
class EnsureKycApproved
{
    public function __construct(private readonly KycService $kyc) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $this->kyc->canInvest($user)) {
            return redirect()
                ->route('portal.profile')
                ->with('status', 'يجب اكتمال التحقق من هويتك (KYC) قبل تنفيذ أي إجراء استثماري.');
        }

        return $next($request);
    }
}
