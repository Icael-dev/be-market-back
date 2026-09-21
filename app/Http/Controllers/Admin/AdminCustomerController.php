<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoints;
use App\Models\LoyaltyTransaction;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->where('role', User::ROLE_CLIENT)
            ->with('profile')
            ->withCount('orders')
            ->withSum('orders as total_spent', 'total')
            ->orderByDesc('created_at');

        if ($request->filled('email')) {
            $query->where('email', 'like', '%'.$request->input('email').'%');
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhereHas('profile', function ($q2) use ($search) {
                        $q2->where('full_name', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%');
                    });
            });
        }

        $paginator = $query->paginate((int) $request->query('per_page', 10));
        $customers = $paginator->getCollection()->map(function (User $user) {
            $profile = $user->profile;

            return [
                'id' => $user->id,
                'user_id' => $user->id,
                'email' => $user->email,
                'full_name' => $profile?->full_name ?? $user->name,
                'phone' => $profile?->phone ?? null,
                'avatar_url' => $profile?->avatar_url ?? null,
                'is_admin' => $user->is_admin,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'orders_count' => (int) ($user->orders_count ?? 0),
                'total_spent' => (float) ($user->total_spent ?? 0),
            ];
        });

        return response()->json([
            'data' => $customers,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $user = User::with('profile')
            ->where('role', User::ROLE_CLIENT)
            ->where('id', $id)
            ->withCount('orders')
            ->withSum('orders as total_spent', 'total')
            ->firstOrFail();

        $profile = $user->profile;

        return response()->json([
            'id' => $user->id,
            'user_id' => $user->id,
            'email' => $user->email,
            'full_name' => $profile?->full_name ?? $user->name,
            'phone' => $profile?->phone ?? null,
            'avatar_url' => $profile?->avatar_url ?? null,
            'is_admin' => $user->is_admin,
            'role' => $user->role,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'orders_count' => (int) ($user->orders_count ?? 0),
            'total_spent' => (float) ($user->total_spent ?? 0),
        ]);
    }

    public function orders(string $id): JsonResponse
    {
        $user = User::with('orders.items.product', 'orders.payments')
            ->where('role', User::ROLE_CLIENT)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json($user->orders);
    }

    public function loyalty(string $id): JsonResponse
    {
        $points = LoyaltyPoints::where('user_id', $id)->first();
        $transactions = LoyaltyTransaction::where('user_id', $id)
            ->with('order')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'points' => $points,
            'transactions' => $transactions,
        ]);
    }

    public function payments(string $id): JsonResponse
    {
        $payments = Payment::where('user_id', $id)
            ->with(['order', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($payments);
    }
}
