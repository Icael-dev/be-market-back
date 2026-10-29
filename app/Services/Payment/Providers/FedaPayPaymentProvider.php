<?php

namespace App\Services\Payment\Providers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\Contracts\PaymentProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class FedaPayPaymentProvider implements PaymentProvider
{
    private string $baseUrl;
    private string $apiKey;
    private string $callbackUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.fedapay.base_url');
        $this->apiKey = config('services.fedapay.api_key');
        $this->callbackUrl = config('services.fedapay.callback_url');
    }

    public function initiate(Order $order, array $data): array
    {
        $this->validateConfig();

        $payload = $this->buildInitiatePayload($order, $data);
        $response = $this->http()->post('/transactions', $payload);

        if (! $response->successful()) {
            Log::error('FedaPay initiate failed', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw ValidationException::withMessages([
                'payment' => 'Failed to initiate payment with provider.',
            ]);
        }

        $responseData = $response->json();
        $transaction = $responseData['v1/transaction'] ?? $responseData;

        if (($transaction['status'] ?? '') === 'declined' || ($transaction['status'] ?? '') === 'cancelled') {
            Log::error('FedaPay initiate declined', [
                'order_id' => $order->id,
                'status' => $transaction['status'],
            ]);
            throw ValidationException::withMessages([
                'payment' => $transaction['description'] ?? 'Payment initiation failed.',
            ]);
        }

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_method' => $data['payment_method'],
            'amount' => $order->total,
            'phone_number' => $data['phone_number'] ?? null,
            'reference' => $transaction['reference'] ?? $transaction['id'] ?? null,
            'status' => 'pending',
            'paid_at' => null,
            'provider_response' => $transaction,
        ]);

        $redirectUrl = $this->getCheckoutUrl($transaction);

        return [
            'payment' => $payment->load('order'),
            'redirect_url' => $redirectUrl,
        ];
    }

    public function verify(string $reference): array
    {
        $this->validateConfig();

        $payment = Payment::where('reference', $reference)->first();

        if (! $payment) {
            return ['found' => false];
        }

        $response = $this->http()->get("/transactions/{$reference}");

        if (! $response->successful()) {
            Log::error('FedaPay verify failed', [
                'reference' => $reference,
                'status' => $response->status(),
            ]);

            return [
                'found' => true,
                'status' => $payment->status,
                'paid_at' => $payment->paid_at,
            ];
        }

        $responseData = $response->json();
        $transaction = $responseData['v1/transaction'] ?? $responseData;
        $status = $transaction['status'] ?? null;

        if ($status === 'approved') {
            $payment->update([
                'status' => 'paid',
                'paid_at' => $transaction['approved_at'] ?? now(),
                'provider_response' => array_merge($payment->provider_response ?? [], $transaction),
            ]);

            $payment->order->update(['payment_status' => 'paid']);

            return [
                'found' => true,
                'status' => 'paid',
                'paid_at' => $payment->paid_at,
            ];
        }

        if ($status === 'declined' || $status === 'cancelled') {
            $payment->update([
                'status' => 'failed',
                'provider_response' => array_merge($payment->provider_response ?? [], $transaction),
            ]);
        }

        return [
            'found' => true,
            'status' => $payment->status,
            'paid_at' => $payment->paid_at,
        ];
    }

    public function handleCallback(array $data): array
    {
        $reference = $data['reference'] ?? $data['external_id'] ?? ($data['v1/transaction'] ?? null);

        if (! $reference) {
            $transaction = $data['v1/transaction'] ?? [];
            $reference = is_array($transaction) ? ($transaction['reference'] ?? $transaction['id'] ?? null) : null;
        }

        if (! $reference) {
            return ['success' => false, 'message' => 'Missing reference in callback'];
        }

        $payment = Payment::where('reference', $reference)->first();

        if (! $payment) {
            return ['success' => false, 'message' => 'Payment not found'];
        }

        if ($payment->status === 'paid') {
            return ['success' => true, 'payment' => $payment->load('order'), 'already_processed' => true];
        }

        $this->verify($reference);

        return [
            'success' => true,
            'payment' => $payment->fresh()->load('order'),
        ];
    }

    private function buildInitiatePayload(Order $order, array $data): array
    {
        $modeMap = [
            'tmoney' => 'togocel',
            'flooz' => 'moov_tg',
        ];

        $payload = [
            'description' => 'Order #'.$order->order_number,
            'amount' => (int) round($order->total),
            'currency' => ['iso' => 'XOF'],
            'callback_url' => $this->callbackUrl,
            'customer' => [
                'firstname' => $order->user?->name ?? 'Customer',
                'lastname' => '',
                'email' => $order->user?->email ?? 'customer@bemarket.com',
                'phone_number' => [
                    'number' => $data['phone_number'] ?? '',
                    'country' => 'tg',
                ],
            ],
            'custom_metadata' => [
                'order_id' => $order->id,
                'payment_method' => $data['payment_method'],
            ],
        ];

        if (isset($modeMap[$data['payment_method']])) {
            $payload['mode'] = $modeMap[$data['payment_method']];
        }

        return $payload;
    }

    private function getCheckoutUrl(array $transaction): ?string
    {
        if (isset($transaction['mode'])) {
            return null;
        }

        $transactionId = $transaction['id'] ?? null;

        if (! $transactionId) {
            return null;
        }

        $response = $this->http()->post("/transactions/{$transactionId}/token");

        if (! $response->successful()) {
            Log::error('FedaPay token generation failed', [
                'transaction_id' => $transactionId,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return null;
        }

        $tokenData = $response->json();

        return $tokenData['url'] ?? null;
    }

    private function http(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->baseUrl($this->baseUrl)
            ->timeout(30);
    }

    private function validateConfig(): void
    {
        if (! $this->apiKey || ! $this->callbackUrl) {
            throw new \RuntimeException('FedaPay configuration is incomplete. Check FEDEPAY_API_KEY, FEDEPAY_CALLBACK_URL.');
        }
    }
}
