<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\OnboardingDocumentsRequest;
use App\Http\Requests\Portal\OnboardingProfileRequest;
use App\Http\Requests\Portal\OnboardingTermsRequest;
use App\Services\Portal\OnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function show(Request $request, OnboardingService $service): View|RedirectResponse
    {
        $user = $request->user();

        // The wizard only appears while onboarding is incomplete. A finished
        // user is sent to the dashboard, unless they just completed it (?done).
        if ($user->hasCompletedOnboarding() && ! $request->boolean('done')) {
            return redirect()->route('portal.dashboard');
        }

        $step = $request->integer('step') ?: null;

        return view('portal.onboarding.index', $service->data($user, $step));
    }

    public function storeProfile(OnboardingProfileRequest $request, OnboardingService $service): RedirectResponse
    {
        $service->saveProfile($request->user(), $request->validated());

        return redirect()->route('portal.onboarding');
    }

    public function storeDocuments(OnboardingDocumentsRequest $request, OnboardingService $service): RedirectResponse
    {
        $service->saveDocuments($request->user(), [
            'identity' => $request->file('identity'),
            'iban' => $request->file('iban'),
            'address' => $request->file('address'),
        ]);

        return redirect()->route('portal.onboarding');
    }

    public function storeTerms(OnboardingTermsRequest $request, OnboardingService $service): RedirectResponse
    {
        $service->complete($request->user());

        return redirect()->route('portal.onboarding', ['done' => 1]);
    }
}
