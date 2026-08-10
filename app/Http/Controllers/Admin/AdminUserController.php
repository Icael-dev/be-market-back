<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;

class AdminUserController extends Controller
{
    /**
     * List staff members (admins and staff), excluding regular clients.
     */
    public function index(): JsonResponse
    {
        $users = User::withTrashed()->with('profile')
            ->whereIn('role', User::STAFF_ROLES)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (User $user) => $this->formatUser($user));

        return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', Rule::in(User::STAFF_ROLES)],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $isAdmin = in_array($data['role'], User::STAFF_ROLES, true);

        $user = DB::transaction(function () use ($data, $isAdmin) {
            $user = User::create([
                'name' => $data['full_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_admin' => $isAdmin,
                'role' => $data['role'],
            ]);

            Profile::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'full_name' => $data['full_name'],
                'phone' => $data['phone'] ?? null,
                'avatar_url' => null,
                'is_admin' => $isAdmin,
                'role' => $data['role'],
            ]);

            return $user;
        });

        return response()->json($this->formatUser($user->load('profile')), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::withTrashed()->with('profile')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['sometimes', Rule::in(User::STAFF_ROLES)],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Prevent an admin from demoting themselves and losing admin access.
        if ($request->user()->id === $user->id && isset($data['role']) && $data['role'] !== User::ROLE_ADMIN) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier votre propre role.'], 422);
        }

        $userUpdates = [];
        $profileUpdates = [];

        if (isset($data['full_name'])) {
            $userUpdates['name'] = $data['full_name'];
            $profileUpdates['full_name'] = $data['full_name'];
        }

        if (isset($data['email'])) {
            $userUpdates['email'] = $data['email'];
            $profileUpdates['email'] = $data['email'];
        }

        if (! empty($data['password'])) {
            $userUpdates['password'] = Hash::make($data['password']);
        }

        if (array_key_exists('phone', $data)) {
            $profileUpdates['phone'] = $data['phone'];
        }

        if (isset($data['role'])) {
            $isAdmin = in_array($data['role'], User::STAFF_ROLES, true);
            $userUpdates['role'] = $data['role'];
            $userUpdates['is_admin'] = $isAdmin;
            $profileUpdates['role'] = $data['role'];
            $profileUpdates['is_admin'] = $isAdmin;
        }

        if (! empty($userUpdates)) {
            $user->update($userUpdates);
        }

        if (! empty($profileUpdates) && $user->profile) {
            $user->profile->update($profileUpdates);
        }

        return response()->json($this->formatUser($user->fresh('profile')));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = User::withTrashed()->findOrFail($id);

        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 422);
        }

        $user->delete();

        if ($user->isStaffMember()) {
            PersonalAccessToken::where('tokenable_id', $user->id)->delete();
        }

        return response()->json(['message' => 'Utilisateur supprime.']);
    }

    public function restore(string $id): JsonResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return response()->json(['message' => 'Utilisateur restaure.', 'user' => $this->formatUser($user->load('profile'))]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'full_name' => $user->profile?->full_name ?? $user->name,
            'email' => $user->email,
            'phone' => $user->profile?->phone,
            'role' => $user->role,
            'is_admin' => (bool) $user->is_admin,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}
