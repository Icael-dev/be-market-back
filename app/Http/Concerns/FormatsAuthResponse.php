<?php

namespace App\Http\Concerns;

use App\Models\User;
use Illuminate\Http\JsonResponse;

trait FormatsAuthResponse
{
    protected function authResponse(User $user, string $token, int $status = 200): JsonResponse
    {
        $user->load('profile');

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
            'profile' => $user->profile,
        ], $status);
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
}
