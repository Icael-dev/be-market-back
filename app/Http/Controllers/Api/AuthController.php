<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
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
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $fullName,
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'is_admin' => false,
            'role' => User::ROLE_CLIENT,
        ]);

        Auth::login($user);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
            'profile' => null,
        ], 201);
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

        Auth::login($user);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
            'profile' => null,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['user' => null, 'profile' => null], 401);
        }

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

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

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

    public function sendResetLink(Request $request): JsonResponse
    {
        $validator = Validator::make($request->only(['email']), [
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $email = $request->input('email');

        // Invalidate any existing token for this email
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        $token = Str::random(60);

        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        // In production, send an email with the reset link.
        // For now, return the token in the response (development mode).
        $resetUrl = config('app.frontend_url', 'http://localhost:5173')."/reset-password/{$token}?email=".urlencode($email);

        // Attempt to send email if mail is configured
        try {
            Mail::raw(
                "Bonjour,\n\nVous avez demande une reinitialisation de mot de passe.\n\nCliquez sur ce lien pour reinitialiser votre mot de passe :\n{$resetUrl}\n\nCe lien expire dans 60 minutes.\n\nSi vous n'avez pas demande cette reinitialisation, ignorez cet email.",
                function ($message) use ($email) {
                    $message->to($email)
                        ->subject('Reinitialisation de mot de passe - BE-Market');
                }
            );
        } catch (\Exception $e) {
            // Mail not configured, log and still return success
            logger()->warning('Failed to send password reset email: '.$e->getMessage());
        }

        return response()->json([
            'message' => 'Si cet email existe, un lien de reinitialisation a ete envoye.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->only(['email', 'token', 'password']), [
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $email = $request->input('email');
        $token = $request->input('token');

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record || ! Hash::check($token, $record->token)) {
            return response()->json(['message' => 'Token invalide ou expire.'], 400);
        }

        // Check token expiration (60 minutes)
        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return response()->json(['message' => 'Token expire. Veuillez refaire la demande.'], 400);
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return response()->json(['message' => 'Mot de passe reinitialise avec succes.']);
    }
}
