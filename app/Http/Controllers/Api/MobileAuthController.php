<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileAccessToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MobileAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 422);
        }

        if (!in_array((string) $user->role, ['consumer', 'client'], true)) {
            return response()->json([
                'message' => 'This account is not allowed for mobile marketplace login.',
            ], 403);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email before logging in.',
            ], 403);
        }

        $user->revokeMobileAccessTokens('consumer-app');
        $user->updateLastLogin();

        $expiresAt = now()->addDays(30);
        $token = $user->issueMobileAccessToken('consumer-app', $expiresAt);

        return response()->json([
            'message' => 'Login successful.',
            'data' => [
                ...$this->consumerPayload($user),
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => $expiresAt->toIso8601String(),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $accessToken = $request->attributes->get('mobile_access_token');

        if ($accessToken instanceof MobileAccessToken) {
            $accessToken->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    private function consumerPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'location' => $user->location,
            'role' => $user->role,
        ];
    }
}
