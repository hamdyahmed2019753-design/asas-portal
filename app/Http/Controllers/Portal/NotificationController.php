<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\NotificationCenterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationCenterService $service): View
    {
        return view('portal.notifications.index', $service->data($request->user(), $request));
    }

    public function markRead(Request $request, string $notification, NotificationCenterService $service): RedirectResponse
    {
        $service->markRead($request->user(), $notification);

        return back();
    }

    public function markAllRead(Request $request, NotificationCenterService $service): RedirectResponse
    {
        $service->markAllRead($request->user());

        return back();
    }
}
