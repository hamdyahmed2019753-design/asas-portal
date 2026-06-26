<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\NewsPortalService;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(NewsPortalService $service): View
    {
        return view('portal.news.index', $service->data());
    }
}
