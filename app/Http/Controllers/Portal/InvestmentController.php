<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Services\Portal\InvestmentPortalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvestmentController extends Controller
{
    public function index(Request $request, InvestmentPortalService $service): View
    {
        return view('portal.investments.index', $service->list($request->user(), $request));
    }

    public function show(Investment $investment, InvestmentPortalService $service): View
    {
        // Ownership enforced before any rendering (admins or the owner only).
        $this->authorize('view', $investment);

        return view('portal.investments.show', $service->details($investment));
    }
}
