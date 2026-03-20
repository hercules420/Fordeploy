<?php

namespace App\Http\Controllers;

use App\Models\ConsumerVerificationCode;
use App\Models\User;
use App\Services\ConsumerVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConsumerVerificationController extends Controller
{
    public function show(Request $request)
    {
        $user = $this->resolvePendingUser($request);

        if (!$user) {
            return redirect()->route('consumer.register')
                ->withErrors(['email' => 'Please register first before verifying your account.']);
        }

        return view('auth.consumer-verify-code', ['email' => $user->email]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $this->resolvePendingUser($request);

        if (!$user) {
            return redirect()->route('consumer.register')
                ->withErrors(['email' => 'Your verification session expired. Please register again.']);
        }

        $record = ConsumerVerificationCode::where('user_id', $user->id)->first();

        if (!$record || $record->expires_at->isPast() || $record->code !== $request->input('code')) {
            if ($record) {
                $record->increment('attempts');
            }

            return back()->withErrors(['code' => 'Invalid or expired verification code.']);
        }

        $user->forceFill(['email_verified_at' => now()])->save();
        $record->delete();

        $request->session()->forget('consumer_verification_user_id');

        return redirect('/')
            ->with('consumer_verified', true)
            ->with('success', 'Email verified successfully. You can now log in on web or open the mobile app.');
    }

    public function resend(Request $request, ConsumerVerificationService $verificationService): RedirectResponse
    {
        $user = $this->resolvePendingUser($request);

        if (!$user) {
            return redirect()->route('consumer.register')
                ->withErrors(['email' => 'Your verification session expired. Please register again.']);
        }

        $verificationService->issueCode($user);

        return back()->with('success', 'A new verification code has been sent to your email.');
    }

    private function resolvePendingUser(Request $request): ?User
    {
        $id = $request->session()->get('consumer_verification_user_id');

        if (!$id) {
            return null;
        }

        return User::where('id', $id)->where('role', 'consumer')->first();
    }
}
