<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\InvestorDashboardService;
use Illuminate\View\View;

class PortalDashboardController extends Controller
{
    public function __invoke(InvestorDashboardService $dashboard): View
    {
        return view('portal.dashboard', $dashboard->for(auth()->user()));
    }
}
