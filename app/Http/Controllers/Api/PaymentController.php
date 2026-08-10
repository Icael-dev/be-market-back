<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::whereHas('order', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with('order')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($payments);
    }

    public function store(PaymentRequest $request): JsonResponse
    {
        $data = $request->only(['order_id', 'payment_method', 'phone_number']);

        if (app()->environment('production') && env('PAYMENT_SANDBOX', true)) {
            return response()->json(['message' => 'Payment sandbox is not allowed in production.'], 500);
        }

        $order = Order::where('user_id', $request->user()->id)
            ->where('id', $data['order_id'])
            ->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['message' => 'Order already paid.'], 400);
        }

        $isSandbox = env('PAYMENT_SANDBOX', true);

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_method' => $data['payment_method'],
            'amount' => $order->total,
            'phone_number' => $data['phone_number'],
            'reference' => 'PAY-'.strtoupper(uniqid()),
            'status' => $isSandbox ? 'paid' : 'pending',
            'paid_at' => $isSandbox ? now() : null,
        ]);

        if ($isSandbox) {
            $order->update(['payment_status' => 'paid']);
        }

        return response()->json($payment->load('order'), 201);
    }

    public function verify(Request $request, string $id): JsonResponse
    {
        $payment = Payment::where('id', $id)
            ->whereHas('order', fn ($q) => $q->where('user_id', $request->user()->id))
            ->first();

        if (! $payment) {
            return response()->json(['message' => 'Payment not found.'], 404);
        }

        return response()->json($payment);
    }

    public function callback(Request $request): JsonResponse
    {
        $secret = env('PAYMENT_CALLBACK_SECRET');

        if (app()->environment('production') && empty($secret)) {
            return response()->json(['message' => 'Callback secret not configured.'], 500);
        }

        if ($secret && $request->header('X-Callback-Secret') !== $secret) {
            return response()->json(['message' => 'Unauthorized callback.'], 401);
        }

        $data = $request->only(['reference', 'status', 'provider_data']);

        $validator = Validator::make($data, [
            'reference' => ['required', 'string'],
            'status' => ['required', 'string'],
            'provider_data' => ['sometimes', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $payment = Payment::where('reference', $data['reference'])->first();

        if (! $payment) {
            return response()->json(['message' => 'Payment not found.'], 404);
        }

        $payment->update([
            'status' => $data['status'],
            'provider_response' => $data['provider_data'] ?? null,
            'paid_at' => $data['status'] === 'paid' ? now() : $payment->paid_at,
        ]);

        if ($data['status'] === 'paid') {
            $payment->order->update(['payment_status' => 'paid']);
        }

        return response()->json($payment->load('order'));
    }
}
