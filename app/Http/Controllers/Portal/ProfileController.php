<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\ProfilePortalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(Request $request, ProfilePortalService $service): View
    {
        return view('portal.profile.index', $service->data($request->user()));
    }
}
