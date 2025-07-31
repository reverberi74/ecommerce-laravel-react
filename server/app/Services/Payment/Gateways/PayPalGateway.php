<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\DTOs\PaymentRequest;
use App\Services\Payment\DTOs\PaymentResponse;
use App\Services\Payment\Exceptions\PaymentException;
use App\Services\Payment\PaymentServiceInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PayPalGateway implements PaymentServiceInterface
{
    protected Client $http;
    protected string $baseUrl;
    protected string $clientId;
    protected string $secret;
    protected string $mode;

    public function __construct()
    {
        $cfg = config('payment.gateways.paypal', []);

        $this->baseUrl  = $cfg['base_url']      ?? 'https://api-m.sandbox.paypal.com';
        $this->clientId = $cfg['client_id']     ?? '';
        $this->secret   = $cfg['client_secret'] ?? ($cfg['secret'] ?? '');
        $this->mode     = $cfg['mode']          ?? 'sandbox';

        $this->http = new Client([
            'base_uri'    => $this->baseUrl,
            'timeout'     => 10.0,
            'http_errors' => false,
            'headers'     => [
                'Accept'     => 'application/json',
                'User-Agent' => $this->userAgent(),
            ],
        ]);
    }

    /**
     * Crea un PayPal Order (intent CAPTURE), salva Payment (pending) e restituisce approve_url + payment_id.
     */
    public function createPayment(PaymentRequest $request): PaymentResponse
    {
        $order = Order::findOrFail($request->orderId);

        try {
            $token = $this->getAccessToken();

            // URL di ritorno/cancel (overridable da gateway_data)
            $returnUrl = data_get($request->gatewayData, 'return_url', config('app.url') . '/payment/return');
            $cancelUrl = data_get($request->gatewayData, 'cancel_url', config('app.url') . '/payment/cancel');

            $amountValue = $this->formatAmount($order->total_amount ?? $order->total ?? 0);

            $payload = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => (string) $order->id,
                    'amount' => [
                        'currency_code' => 'EUR',
                        'value' => $amountValue,
                    ],
                ]],
                'application_context' => [
                    'return_url' => $returnUrl,
                    'cancel_url' => $cancelUrl,
                    // opzionali: 'brand_name', 'shipping_preference', ecc.
                ],
            ];

            $res = $this->http->post('/v2/checkout/orders', [
                'headers' => $this->authHeaders($token),
                'json'    => $payload,
            ]);

            $json = json_decode((string) $res->getBody(), true);

            if ($res->getStatusCode() >= 300 || empty($json['id'])) {
                $msg = 'PayPal create order failed';
                Log::error($msg, ['status' => $res->getStatusCode(), 'body' => $json]);
                return new PaymentResponse(success: false, error: $msg);
            }

            $paypalOrderId = $json['id'];
            $approveUrl    = $this->extractApproveUrl($json['links'] ?? []);

            // Salva Payment in pending
            $payment = Payment::create([
                'order_id'               => $order->id,
                'payment_method'         => 'paypal',
                'gateway_transaction_id' => $paypalOrderId,
                'amount'                 => (float) $amountValue,
                'currency'               => 'EUR',
                'status'                 => 'pending',
                'gateway_response'       => $json, // JSON completo dell'Order creato
            ]);

            return new PaymentResponse(
                success: true,
                transactionId: $paypalOrderId,
                data: [
                    'payment_id'  => $payment->id,
                    'approve_url' => $approveUrl,
                ],
            );
        } catch (\Throwable $e) {
            Log::error('PayPal createPayment exception', ['error' => $e->getMessage()]);
            return new PaymentResponse(success: false, error: 'Unable to create PayPal order: ' . $e->getMessage());
        }
    }

    /**
     * Cattura un PayPal Order e aggiorna Payment.
     */
    public function confirmPayment(string $paymentId): PaymentResponse
    {
        $payment = Payment::findOrFail($paymentId);

        try {
            $token = $this->getAccessToken();

            $orderId = $payment->gateway_transaction_id; // ID dell'Order PayPal
            if (!$orderId) {
                return new PaymentResponse(success: false, error: 'Missing PayPal Order ID on payment.');
            }

            $res = $this->http->post("/v2/checkout/orders/{$orderId}/capture", [
                'headers' => $this->authHeaders($token),
            ]);

            $json = json_decode((string) $res->getBody(), true);

            if ($res->getStatusCode() >= 300) {
                $msg = data_get($json, 'message', 'PayPal capture failed');
                Log::error('PayPal confirmPayment failed', ['status' => $res->getStatusCode(), 'body' => $json]);
                $payment->update([
                    'status'           => 'failed',
                    'gateway_response' => $json,
                ]);
                return new PaymentResponse(success: false, error: $msg);
            }

            $paypalStatus = strtoupper((string) data_get($json, 'status', ''));
            $captureId    = $this->extractCaptureId($json);

            $status = $paypalStatus === 'COMPLETED' ? 'completed' : 'failed';

            $payment->update([
                'status'           => $status,
                'paid_at'          => $status === 'completed' ? now() : null,
                'gateway_response' => $json + ['_extracted_capture_id' => $captureId],
            ]);

            return new PaymentResponse(
                success: $status === 'completed',
                transactionId: $orderId,
                data: ['status' => $status],
                error: $status !== 'completed' ? 'Payment not completed' : null
            );
        } catch (\Throwable $e) {
            Log::error('PayPal confirmPayment exception', ['error' => $e->getMessage()]);
            return new PaymentResponse(success: false, error: 'Unable to capture PayPal order: ' . $e->getMessage());
        }
    }

    /**
     * Esegue un refund su una capture PayPal.
     */
    public function refundPayment(string $paymentId, float $amount): PaymentResponse
    {
        $payment = Payment::findOrFail($paymentId);

        try {
            $token = $this->getAccessToken();

            // Cerchiamo un capture_id dentro la risposta memorizzata
            $captureId = $this->extractCaptureId($payment->gateway_response ?? []);
            if (!$captureId) {
                Log::warning('PayPal refund: missing capture_id on payment gateway_response', ['payment_id' => $payment->id]);
                return new PaymentResponse(success: false, error: 'Missing capture_id to refund.');
            }

            $payload = [
                'amount' => [
                    'value'         => $this->formatAmount($amount),
                    'currency_code' => 'EUR',
                ],
            ];

            $res = $this->http->post("/v2/payments/captures/{$captureId}/refund", [
                'headers' => $this->authHeaders($token),
                'json'    => $payload,
            ]);

            $json = json_decode((string) $res->getBody(), true);

            if ($res->getStatusCode() >= 300 || empty($json['id'])) {
                $msg = 'PayPal refund failed';
                Log::error($msg, ['status' => $res->getStatusCode(), 'body' => $json]);
                return new PaymentResponse(success: false, error: $msg);
            }

            return new PaymentResponse(
                success: true,
                transactionId: (string) $json['id'],
                data: ['refunded_amount' => (float) $amount]
            );
        } catch (\Throwable $e) {
            Log::error('PayPal refundPayment exception', ['error' => $e->getMessage()]);
            return new PaymentResponse(success: false, error: 'Unable to refund PayPal capture: ' . $e->getMessage());
        }
    }

    /**
     * Fase futura: salvataggio metodo (vault). Non implementato per PayPal Standard.
     */
    public function savePaymentMethod(int $userId, array $data): array
    {
        throw new PaymentException('savePaymentMethod not implemented for PayPal Standard.');
    }

    /**
     * Fase futura: lista metodi (vault). Non implementato per PayPal Standard.
     */
    public function getPaymentMethods(int $userId): Collection
    {
        return collect();
    }

    // ----------------------------------------
    // Helpers
    // ----------------------------------------

    protected function getAccessToken(): string
    {
        try {
            $res = $this->http->post('/v1/oauth2/token', [
                'auth'        => [$this->clientId, $this->secret],
                'form_params' => ['grant_type' => 'client_credentials'],
                'headers'     => [
                    // OAuth2 richiede form-urlencoded
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);

            $json = json_decode((string) $res->getBody(), true);

            if ($res->getStatusCode() >= 300 || empty($json['access_token'])) {
                Log::error('PayPal getAccessToken failed', ['status' => $res->getStatusCode(), 'body' => $json]);
                throw new \RuntimeException('PayPal OAuth2 token error');
            }

            return (string) $json['access_token'];
        } catch (GuzzleException $e) {
            throw new \RuntimeException('PayPal OAuth2 HTTP error: ' . $e->getMessage(), previous: $e);
        }
    }

    protected function userAgent(): string
    {
        // Se definito in config/env, usa quello
        if ($ua = config('payment.user_agent')) {
            return $ua;
        }

        // Fallback dinamico, coerente con l’app attuale
        return sprintf(
            '%s-Payments/%s (Laravel %s; PHP %s; %s)',
            str_replace(' ', '-', config('app.name', 'ecommerce-laravel-react')),
            config('app.version', 'dev'),
            app()->version(),
            PHP_VERSION,
            config('app.env')
        );
    }


    protected function authHeaders(string $accessToken): array
    {
        return [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type'  => 'application/json',
        ];
    }

    protected function extractApproveUrl(array $links): ?string
    {
        foreach ($links as $link) {
            if (($link['rel'] ?? '') === 'approve') {
                return $link['href'] ?? null;
            }
        }
        return null;
    }

    /**
     * Estrae la prima capture id disponibile dalla risposta PayPal.
     */
    protected function extractCaptureId(array $response): ?string
    {
        // capture id spesso è in: purchase_units[0].payments.captures[0].id
        $captureId = data_get($response, 'purchase_units.0.payments.captures.0.id');
        if ($captureId) {
            return (string) $captureId;
        }
        // Alcune risposte possono avere un array 'links' con rel 'up' verso capture, ma non affidabile per ID.
        return data_get($response, '_extracted_capture_id'); // fallback se lo abbiamo aggiunto noi
    }

    protected function formatAmount(float|int|string $amount): string
    {
        // PayPal accetta stringhe con max 2 decimali.
        return number_format((float) $amount, 2, '.', '');
    }
}
