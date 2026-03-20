<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayMongoService
{
    protected string $baseUrl;
    protected string $secretKey;
    protected bool $verifySsl;

    public function __construct()
    {
        $this->baseUrl = config('services.paymongo.base_url', 'https://api.paymongo.com/v1');
        $this->secretKey = config('services.paymongo.secret_key', '');
        $this->verifySsl = (bool) config('services.paymongo.verify_ssl', true);
    }

    /**
     * Create a PayMongo Checkout Session for subscription payment.
     */
    public function createCheckoutSession(array $params): ?array
    {
        if (empty($this->secretKey)) {
            Log::error('PayMongo secret key is not configured');
            return null;
        }

        $amount = (int) ($params['amount'] ?? 0);
        $lineItems = $params['line_items'] ?? [
            [
                'currency' => 'PHP',
                'amount' => $amount,
                'name' => $params['plan_name'] ?? 'Payment',
                'quantity' => 1,
                'description' => $params['description'] ?? 'Payment',
            ],
        ];

        $paymentMethodTypes = $params['payment_method_types'] ?? [
            'gcash',
            'grab_pay',
            'paymaya',
            'card',
        ];

        $metadata = $params['metadata'] ?? [
            'user_id' => $params['user_id'] ?? null,
            'farm_owner_id' => $params['farm_owner_id'] ?? null,
            'plan' => $params['plan'] ?? null,
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($this->secretKey . ':'),
        ])->withOptions([
            'verify' => $this->verifySsl,
        ])->post("{$this->baseUrl}/checkout_sessions", [
            'data' => [
                'attributes' => [
                    'send_email_receipt' => $params['send_email_receipt'] ?? true,
                    'show_description' => $params['show_description'] ?? true,
                    'show_line_items' => $params['show_line_items'] ?? true,
                    'cancel_url' => $params['cancel_url'],
                    'success_url' => $params['success_url'],
                    'description' => $params['description'],
                    'line_items' => $lineItems,
                    'payment_method_types' => $paymentMethodTypes,
                    'metadata' => array_filter(
                        $metadata,
                        static fn ($value) => $value !== null && $value !== ''
                    ),
                ],
            ],
        ]);

        if (!$response->successful()) {
            Log::error('PayMongo Checkout Session Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        return $response->json()['data'];
    }

    /**
     * Create a PayMongo Payment Link (fallback method).
     */
    public function createPaymentLink(array $params): ?array
    {
        if (empty($this->secretKey)) {
            Log::error('PayMongo secret key is not configured');
            return null;
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($this->secretKey . ':'),
        ])->withOptions([
            'verify' => $this->verifySsl,
        ])->post("{$this->baseUrl}/links", [
            'data' => [
                'attributes' => [
                    'amount' => $params['amount'],
                    'description' => $params['description'],
                    'remarks' => $params['remarks'],
                ],
            ],
        ]);

        if (!$response->successful()) {
            Log::error('PayMongo Payment Link Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        return $response->json()['data'];
    }

    /**
     * Retrieve a checkout session by ID.
     */
    public function retrieveCheckoutSession(string $checkoutId): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->secretKey . ':'),
        ])->withOptions([
            'verify' => $this->verifySsl,
        ])->get("{$this->baseUrl}/checkout_sessions/{$checkoutId}");

        if (!$response->successful()) {
            Log::error('PayMongo Retrieve Checkout Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        return $response->json()['data'];
    }

    /**
     * Retrieve a payment by ID.
     */
    public function retrievePayment(string $paymentId): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->secretKey . ':'),
        ])->withOptions([
            'verify' => $this->verifySsl,
        ])->get("{$this->baseUrl}/payments/{$paymentId}");

        if (!$response->successful()) {
            return null;
        }

        return $response->json()['data'];
    }

    /**
     * Retrieve a payment link by ID.
     */
    public function retrievePaymentLink(string $linkId): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->secretKey . ':'),
        ])->withOptions([
            'verify' => $this->verifySsl,
        ])->get("{$this->baseUrl}/links/{$linkId}");

        if (!$response->successful()) {
            Log::error('PayMongo Retrieve Link Error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'link_id' => $linkId,
            ]);
            return null;
        }

        return $response->json()['data'];
    }

    /**
     * Create a webhook endpoint in PayMongo.
     */
    public function createWebhook(string $url, array $events = ['checkout_session.payment.paid']): ?array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($this->secretKey . ':'),
        ])->withOptions([
            'verify' => $this->verifySsl,
        ])->post("{$this->baseUrl}/webhooks", [
            'data' => [
                'attributes' => [
                    'url' => $url,
                    'events' => $events,
                ],
            ],
        ]);

        if (!$response->successful()) {
            Log::error('PayMongo Create Webhook Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        return $response->json()['data'];
    }

    /**
     * Verify the webhook signature from PayMongo.
     */
    public function verifyWebhookSignature(string $payload, string $signatureHeader): bool
    {
        $webhookSecret = config('services.paymongo.webhook_secret');

        if (empty($webhookSecret)) {
            Log::warning('PayMongo webhook secret is not configured, skipping verification');
            return true; // Skip verification if secret not set
        }

        // PayMongo sends signature as: t=<timestamp>,te=<test_signature>,li=<live_signature>
        $parts = explode(',', $signatureHeader);
        $timestamp = null;
        $signatures = [];

        foreach ($parts as $part) {
            $segments = explode('=', $part, 2);
            if (count($segments) !== 2) {
                continue;
            }

            [$key, $value] = $segments;
            if ($key === 't') {
                $timestamp = $value;
            } elseif (in_array($key, ['te', 'li'])) {
                $signatures[] = $value;
            }
        }

        if (!$timestamp || empty($signatures)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', "{$timestamp}.{$payload}", $webhookSecret);

        foreach ($signatures as $sig) {
            if (hash_equals($expectedSignature, $sig)) {
                return true;
            }
        }

        return false;
    }
}
