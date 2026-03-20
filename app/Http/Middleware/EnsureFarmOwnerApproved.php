<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureFarmOwnerApproved
{
    private array $allowedRouteNames = [
        'farmowner.pending',
        'farmowner.dashboard',
        'farmowner.profile',
        'farmowner.update_profile',
        'farmowner.subscriptions',
        'farmowner.support.index',
        'farmowner.support.store',
        'farmowner.support.show',
        'farmowner.support.reply',
        'farmowner.logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || !$user->isFarmOwner()) {
            return $next($request);
        }

        $routeName = (string) $request->route()?->getName();

        if (in_array($routeName, $this->allowedRouteNames, true)) {
            return $next($request);
        }

        $farmOwner = $user->farmOwner;

        if (!$farmOwner) {
            return redirect()->route('farmowner.register')
                ->with('error', 'Farm owner profile not found. Please complete registration.');
        }

        if ($farmOwner->permit_status !== 'approved') {
            return redirect()
                ->route('farmowner.pending')
                ->with('error', 'This feature is locked until Super Admin approves your farm account.');
        }

        return $next($request);
    }
}
