<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the investor portal:
 *  - guests are sent to the login page,
 *  - admins are redirected to the Filament panel (the portal is for members/investors),
 *  - members and investors are allowed through.
 */
class InvestorPortalMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if ($user->hasRole('admin')) {
            return redirect('/admin');
        }

        return $next($request);
    }
}
