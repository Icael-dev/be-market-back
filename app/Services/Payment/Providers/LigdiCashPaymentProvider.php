<?php

namespace App\Services\Payment\Providers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\Contracts\PaymentProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LigdiCashPaymentProvider implements PaymentProvider
{
    private string $baseUrl;
    private string $apiKey;
    private string $apiToken;
    private string $callbackUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.ligdicash.base_url');
        $this->apiKey = config('services.ligdicash.api_key');
        $this->apiToken = config('services.ligdicash.api_token');
        $this->callbackUrl = config('services.ligdicash.callback_url');
    }

    public function initiate(Order $order, array $data): array
    {
        $this->validateConfig();

        $payload = $this->buildInitiatePayload($order, $data);
        $response = $this->http()->post('straight/checkout-invoice/create', $payload);

        if (! $response->successful()) {
            Log::error('LigdiCash initiate failed', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw ValidationException::withMessages([
                'payment' => 'Failed to initiate payment with provider.',
            ]);
        }

        $responseData = $response->json();

        if (($responseData['response_code'] ?? '') !== '00') {
            Log::error('LigdiCash initiate error response', [
                'order_id' => $order->id,
                'response' => $responseData,
            ]);
            throw ValidationException::withMessages([
                'payment' => $responseData['response_text'] ?? 'Payment initiation failed.',
            ]);
        }

        $token = $responseData['token'] ?? null;
        $redirectUrl = $responseData['checkout_url'] ?? null;

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_method' => $data['payment_method'],
            'amount' => $order->total,
            'phone_number' => $data['phone_number'] ?? null,
            'reference' => $payload['invoice']['external_id'],
            'status' => 'pending',
            'paid_at' => null,
            'provider_response' => $responseData,
        ]);

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

        $token = $payment->provider_response['token'] ?? null;

        if (! $token) {
            return ['found' => true, 'status' => $payment->status, 'paid_at' => $payment->paid_at];
        }

        $response = $this->http()->get("redirect/checkout-invoice/confirm/?invoiceToken={$token}");

        if (! $response->successful()) {
            Log::error('LigdiCash verify failed', [
                'reference' => $reference,
                'status' => $response->status(),
            ]);
            return ['found' => true, 'status' => $payment->status, 'paid_at' => $payment->paid_at];
        }

        $responseData = $response->json();

        if (($responseData['status'] ?? '') === 'completed' && ($responseData['response_code'] ?? '') === '00') {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'provider_response' => array_merge($payment->provider_response ?? [], $responseData),
            ]);

            $payment->order->update(['payment_status' => 'paid']);

            return [
                'found' => true,
                'status' => 'paid',
                'paid_at' => $payment->paid_at,
            ];
        }

        if (($responseData['status'] ?? '') === 'failed' || ($responseData['response_code'] ?? '') !== '00') {
            $payment->update([
                'status' => 'failed',
                'provider_response' => array_merge($payment->provider_response ?? [], $responseData),
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
        $reference = $data['external_id'] ?? $data['invoiceToken'] ?? ($data['transaction_id'] ?? null);

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
        $externalId = 'ORDER-'.$order->id.'-'.strtoupper(uniqid());

        return [
            'invoice' => [
                'total_amount' => (int) round($order->total),
                'devise' => 'XOF',
                'description' => 'Order #'.$order->order_number,
                'customer' => $order->user?->email ?? 'customer@bemarket.com',
                'customer_firstname' => $order->user?->name ?? 'Customer',
                'customer_lastname' => '',
                'customer_email' => $order->user?->email ?? 'customer@bemarket.com',
                'external_id' => $externalId,
                'otp' => $data['phone_number'] ?? '',
            ],
            'store' => [
                'name' => config('app.name', 'BeMarket'),
            ],
            'actions' => [
                'callback_url' => $this->callbackUrl,
            ],
            'custom_data' => [
                'transaction_id' => $externalId,
                'order_id' => $order->id,
                'payment_method' => $data['payment_method'],
            ],
        ];
    }

    private function http(): PendingRequest
    {
        return Http::withHeaders([
            'Apikey' => $this->apiKey,
            'Authorization' => 'Bearer '.$this->apiToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->baseUrl($this->baseUrl)
            ->timeout(30);
    }

    private function validateConfig(): void
    {
        if (! $this->apiKey || ! $this->apiToken || ! $this->callbackUrl) {
            throw new \RuntimeException('LigdiCash configuration is incomplete. Check LIGDICASH_API_KEY, LIGDICASH_API_TOKEN, LIGDICASH_CALLBACK_URL.');
        }
    }
}