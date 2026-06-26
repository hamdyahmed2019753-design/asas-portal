<?php

namespace App\Http\Controllers\Portal;

use App\Exceptions\DuplicateInterestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ContractInterestRequest;
use App\Models\Contract;
use App\Models\ContractInterest;
use App\Services\Portal\ContractInterestService;
use Illuminate\Http\RedirectResponse;

class ContractInterestController extends Controller
{
    public function store(ContractInterestRequest $request, Contract $contract, ContractInterestService $service): RedirectResponse
    {
        // KYC-approved investors only (policy gate).
        $this->authorize('create', ContractInterest::class);

        try {
            $service->express($request->user(), $contract, $request->input('notes'));
        } catch (DuplicateInterestException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'تم استلام طلب اهتمامك بنجاح، وسنتواصل معك قريبًا.');
    }
}
