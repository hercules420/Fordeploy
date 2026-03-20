<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\ConsumerRegistrationRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\ConsumerVerificationService;

class ConsumerRegistrationController extends Controller
{
    public function store(ConsumerRegistrationRequest $request, ConsumerVerificationService $verificationService)
    {
        try {
            $validated = $request->validated();

            $user = DB::transaction(function () use ($validated, $verificationService) {
                $user = User::create([
                    'name'         => $validated['full_name'],
                    'email'        => $validated['email'],
                    'phone'        => $validated['phone_number'],
                    'password'     => $validated['password'],
                    'role'         => 'consumer',
                    'status'       => 'active',
                    'email_verified_at' => null,
                ]);

                $verificationService->issueCode($user);

                return $user;
            });

            session(['consumer_verification_user_id' => $user->id]);

            Log::info('Consumer registered successfully', [
                'user_id' => $user->id,
                'email' => $validated['email'],
            ]);

            return redirect()
                ->route('consumer.verify.form')
                ->with('success', 'Registration complete. Enter the verification code sent to your email.');
        } catch (\Exception $e) {
            Log::error('Consumer registration failed', ['error' => $e->getMessage()]);
            return back()->withErrors([
                'error' => 'Registration failed. Please use a real email address that can receive the verification code.',
            ]);
        }
    }
}