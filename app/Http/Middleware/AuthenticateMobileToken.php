<?php

namespace App\Http\Middleware;

use App\Models\MobileAccessToken;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = $request->bearerToken();

        if (!$plainTextToken) {
            return $this->unauthorized('Authentication token is required.');
        }

        $accessToken = MobileAccessToken::with('user')
            ->where('token_hash', hash('sha256', $plainTextToken))
            ->first();

        if (!$accessToken || $accessToken->isExpired()) {
            return $this->unauthorized('Authentication token is invalid or expired.');
        }

        $user = $accessToken->user;

        if (!$user instanceof User) {
            return $this->unauthorized('Authentication token is invalid.');
        }

        if (!in_array((string) $user->role, ['consumer', 'client'], true)) {
            return response()->json([
                'message' => 'This endpoint is only available for marketplace consumers.',
            ], 403);
        }

        $accessToken->forceFill(['last_used_at' => now()])->save();

        $request->attributes->set('mobile_access_token', $accessToken);
        $request->setUserResolver(static fn () => $user);

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], 401);
    }
}