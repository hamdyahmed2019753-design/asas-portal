<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ChangePasswordRequest;
use App\Http\Requests\Portal\UpdateProfileRequest;
use App\Services\Portal\AccountSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request, AccountSecurityService $service): View
    {
        return view('portal.settings.index', $service->data($request->user(), $request->session()->getId()));
    }

    public function updateProfile(UpdateProfileRequest $request, AccountSecurityService $service): RedirectResponse
    {
        $service->updateProfile($request->user(), $request->validated());

        return back()->with('status', 'تم تحديث بياناتك بنجاح.');
    }

    public function updatePassword(ChangePasswordRequest $request, AccountSecurityService $service): RedirectResponse
    {
        $service->changePassword($request->user(), $request->validated()['password']);

        return back()->with('status', 'تم تغيير كلمة المرور بنجاح.');
    }

    public function logoutOtherSessions(Request $request, AccountSecurityService $service): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $service->logoutOtherSessions($request->user(), $request->session()->getId());

        return back()->with('status', 'تم تسجيل الخروج من بقية الأجهزة.');
    }
}
