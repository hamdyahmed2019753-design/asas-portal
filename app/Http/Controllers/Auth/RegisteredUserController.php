<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Admin\NotifyAdmins;
use App\Enums\AdminNotificationCategory;
use App\Enums\AdminNotificationPriority;
use App\Filament\Resources\InvestorResource;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        // kyc_status defaults to "pending" at the DB level (not mass-assignable).
        // Every self-registered account is a member — never a direct investor.
        $user->assignRole('member');

        event(new Registered($user));

        NotifyAdmins::send(new AdminNotification(
            title: 'مستثمر جديد في انتظار التأهيل',
            body: "قام «{$user->name}» بإنشاء حساب جديد بانتظار إتمام التأهيل.",
            category: AdminNotificationCategory::User,
            priority: AdminNotificationPriority::Medium,
            actor: $user,
            target: $user,
            url: InvestorResource::getUrl('view', ['record' => $user]),
            actionLabel: 'فتح صفحة المستثمر',
        ));

        Auth::login($user);

        // Email must be verified before reaching the portal; the verification
        // notice (with resend) is shown first. After verifying → onboarding.
        return redirect()->route('verification.notice');
    }
}
