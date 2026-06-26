<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\NewsUpdate;
use App\Services\Dashboard\DashboardMetrics;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(DashboardMetrics $metrics): View
    {
        return view('portal.landing', [
            'featured' => Contract::publicVisible()->latest()->take(6)->get(),
            'news' => NewsUpdate::published()->latest('published_at')->take(3)->get(),
            'stats' => [
                'openContracts' => $metrics->openContracts(),
                'investors' => $metrics->totalInvestors(),
                'invested' => $metrics->totalInvestments(),
            ],
        ]);
    }
}
