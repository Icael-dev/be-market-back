<?php

namespace App\Http\Concerns;

use App\Models\User;
use Illuminate\Http\JsonResponse;

trait FormatsAuthResponse
{
    protected function authResponse(User $user, string $token, int $status = 200): JsonResponse
    {
        $user->load('profile');

        $response = response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
            'profile' => $user->profile,
        ], $status);

        return $this->withAuthCookie($response, $token);
    }

    protected function currentUserResponse(User $user): JsonResponse
    {
        $user->load('profile');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
            'profile' => $user->profile,
        ]);
    }

    protected function withAuthCookie(JsonResponse $response, string $token): JsonResponse
    {
        $domain = config('auth.cookie_domain', null);

        $response->headers->setCookie(
            cookie(
                name: 'be_market_token',
                value: $token,
                minutes: 60 * 24 * 7,
                path: '/',
                domain: $domain,
                secure: config('app.env') === 'production',
                httpOnly: true,
                sameSite: 'lax'
            )
        );

        return $response;
    }
}
