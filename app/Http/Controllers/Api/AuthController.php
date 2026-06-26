<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\FormatsAuthResponse;
use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use FormatsAuthResponse;

    public function register(Request $request): JsonResponse
    {
        $fullName = $request->input('full_name') ?? $request->input('name');

        $validator = Validator::make([
            'full_name' => $fullName,
            'email' => $request->input('email'),
            'password' => $request->input('password'),
        ], [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $fullName,
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'is_admin' => false,
        ]);

        Profile::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'full_name' => $fullName,
            'phone' => null,
            'avatar_url' => null,
            'is_admin' => false,
        ]);

        $token = $user->createToken('api-token')->accessToken;

        return $this->authResponse($user, $token, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->only(['email', 'password']), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->input('email'))->first();
        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $token = $user->createToken('api-token')->accessToken;

        return $this->authResponse($user, $token);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->token();
        if ($token) {
            $token->revoke();
        }

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function user(Request $request): JsonResponse
    {
        return $this->currentUserResponse($request->user());
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->token()?->revoke();
        $token = $user->createToken('api-token')->accessToken;

        return $this->authResponse($user, $token);
    }
}
