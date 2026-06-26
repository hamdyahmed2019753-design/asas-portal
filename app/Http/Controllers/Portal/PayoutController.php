<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\PayoutPortalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function index(Request $request, PayoutPortalService $service): View
    {
        return view('portal.payouts.index', $service->data($request->user(), $request));
    }
}
