<?php

namespace App\Services\Payment\Providers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\Contracts\PaymentProvider;

class SandboxPaymentProvider implements PaymentProvider
{
    public function initiate(Order $order, array $data): array
    {
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_method' => $data['payment_method'],
            'amount' => $order->total,
            'phone_number' => $data['phone_number'] ?? null,
            'reference' => 'PAY-'.strtoupper(uniqid()),
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $order->update(['payment_status' => 'paid']);

        return [
            'payment' => $payment->load('order'),
            'redirect_url' => null,
        ];
    }

    public function verify(string $reference): array
    {
        $payment = Payment::where('reference', $reference)->first();

        if (! $payment) {
            return ['found' => false];
        }

        return [
            'found' => true,
            'status' => $payment->status,
            'paid_at' => $payment->paid_at,
        ];
    }

    public function handleCallback(array $data): array
    {
        $reference = $data['reference'] ?? $data['external_id'] ?? $data['invoiceToken'] ?? $data['transaction_id'] ?? null;

        if (! $reference) {
            return ['success' => false, 'message' => 'Missing reference in callback'];
        }

        $payment = Payment::where('reference', $reference)->first();

        if (! $payment) {
            return ['success' => false, 'message' => 'Payment not found'];
        }

        $payment->update([
            'status' => $data['status'],
            'provider_response' => $data['provider_data'] ?? null,
            'paid_at' => $data['status'] === 'paid' ? now() : $payment->paid_at,
        ]);

        if ($data['status'] === 'paid') {
            $payment->order->update(['payment_status' => 'paid']);
        }

        return ['success' => true, 'payment' => $payment->load('order')];
    }
}