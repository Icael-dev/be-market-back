<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\Contracts\PaymentProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentProvider $paymentProvider
    ) {}

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

        if (app()->environment('production') && config('payment.sandbox')) {
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

        $result = $this->paymentProvider->initiate($order, $data);

        $data = $result['payment']->load('order')->toArray();
        $data['redirect_url'] = $result['redirect_url'];

        return response()->json($data, 201);
    }

    public function verify(Request $request, string $id): JsonResponse
    {
        $payment = Payment::where('id', $id)
            ->whereHas('order', fn ($q) => $q->where('user_id', $request->user()->id))
            ->first();

        if (! $payment) {
            return response()->json(['message' => 'Payment not found.'], 404);
        }

        $result = $this->paymentProvider->verify($payment->reference);

        if (! $result['found']) {
            return response()->json(['message' => 'Payment not found.'], 404);
        }

        return response()->json($payment->fresh()->load('order'));
    }

    public function callback(Request $request): JsonResponse
    {
        $secret = config('payment.callback_secret');

        if (app()->environment('production') && empty($secret)) {
            return response()->json(['message' => 'Callback secret not configured.'], 500);
        }

        if ($secret && $request->header('X-Callback-Secret') !== $secret) {
            return response()->json(['message' => 'Unauthorized callback.'], 401);
        }

        $data = array_merge($request->all(), $request->query());

        $validator = Validator::make($data, [
            'reference' => ['sometimes', 'string'],
            'external_id' => ['sometimes', 'string'],
            'invoiceToken' => ['sometimes', 'string'],
            'transaction_id' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string'],
            'provider_data' => ['sometimes', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = $this->paymentProvider->handleCallback($data);

        if (! $result['success']) {
            return response()->json(['message' => $result['message']], 404);
        }

        return response()->json($result['payment']);
    }
}
