<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ConsumerVerificationService;
use App\Services\AttendanceAutomationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(
        LoginRequest $request,
        ConsumerVerificationService $verificationService,
        AttendanceAutomationService $attendanceAutomation,
    ): RedirectResponse
{
    $request->authenticate();
    $request->session()->regenerate();

    $user = Auth::user();

    $attendanceAutomation->handleLogin($user);

    if ($user->isDepartmentRole() && !$user->hasVerifiedEmail()) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return back()->withErrors([
            'email' => 'Please verify your email first. Check your inbox for the verification link.',
        ]);
    }

    // Ensure Super Admins go to their specific Poultry Admin portal
    if ($user->isSuperAdmin()) {
        return redirect()->route('superadmin.dashboard');
    }

    if ($user->role === 'client') {
        return redirect()->route('client.dashboard');
    }

    if ($user->isFarmOwner()) {
        $farmOwner = $user->farmOwner;

        if ($farmOwner && $farmOwner->permit_status !== 'approved') {
            return redirect()->route('farmowner.pending')
                ->with('success', 'Your farm owner account is still waiting for Super Admin approval.');
        }

        return redirect()->route('farmowner.dashboard');
    }

    if ($user->isHR()) {
        return redirect()->route('hr.users.index');
    }

    if ($user->isDepartmentRole()) {
        $routeName = $user->departmentDashboardRouteName();

        if ($routeName) {
            return redirect()->route($routeName);
        }

        return redirect()->route('dashboard');
    }

    if ($user->isConsumer()) {
        if (!$user->hasVerifiedEmail()) {
            $verificationService->issueCode($user);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->put('consumer_verification_user_id', $user->id);

            return redirect()->route('consumer.verify.form')
                ->withErrors(['email' => 'Verify your email first. A fresh code has been sent to your inbox.']);
        }

        return redirect()->route('products.index');
    }

    // Only fallback to home if no role matches
    return redirect('/');
}

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // 1. Get the role BEFORE logging out so we know where to send them
        $user = Auth::user();
        $role = $user ? $user->role : null;

        app(AttendanceAutomationService::class)->handleLogout($user);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 2. Redirect based on the captured role
        if ($role === 'superadmin') {
            // This sends Super Admins back to the main portal page
            return redirect('/'); 
        }

        // This sends Clients/Consumers back to the specific Login Page
        return redirect('/login');
    }
}