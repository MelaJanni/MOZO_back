<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MercadoPagoService
{
    protected $accessToken;
    protected $baseUrl;

    public function __construct()
    {
        $this->accessToken = config('services.mercado_pago.access_token', env('MERCADO_PAGO_ACCESS_TOKEN'));
        $this->baseUrl = 'https://api.mercadopago.com';
    }

    public function createPreference(array $data)
    {
        $preferenceData = [
            'items' => [
                [
                    'title' => $data['title'] ?? 'Suscripción MOZO QR',
                    'quantity' => $data['quantity'] ?? 1,
                    'unit_price' => (float) $data['unit_price'],
                    'currency_id' => $data['currency_id'] ?? 'ARS',
                ]
            ],
            'external_reference' => $data['external_reference'] ?? null,
            'back_urls' => $data['back_urls'] ?? [],
            'auto_return' => $data['auto_return'] ?? 'approved',
            'notification_url' => $data['notification_url'] ?? null,
        ];

        if (isset($data['payer'])) {
            $preferenceData['payer'] = $data['payer'];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/checkout/preferences', $preferenceData);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Error creating MercadoPago preference', [
            'status' => $response->status(),
            'response' => $response->body(),
            'data' => $preferenceData,
        ]);

        throw new \Exception('Error creating MercadoPago preference: ' . $response->body());
    }

    public function getPayment($paymentId)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
        ])->get($this->baseUrl . '/v1/payments/' . $paymentId);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Error getting MercadoPago payment', [
            'payment_id' => $paymentId,
            'status' => $response->status(),
            'response' => $response->body(),
        ]);

        throw new \Exception('Error getting payment from MercadoPago');
    }

    public function verifyWebhook($data)
    {
        // Verificar que el webhook viene de MercadoPago
        if (!isset($data['type']) || !isset($data['data']['id'])) {
            return false;
        }

        return true;
    }

    public function processWebhookPayment($paymentId)
    {
        try {
            $payment = $this->getPayment($paymentId);

            return [
                'id' => $payment['id'],
                'status' => $payment['status'],
                'status_detail' => $payment['status_detail'] ?? null,
                'external_reference' => $payment['external_reference'] ?? null,
                'payment_method_id' => $payment['payment_method_id'] ?? null,
                'payment_type_id' => $payment['payment_type_id'] ?? null,
                'transaction_amount' => $payment['transaction_amount'] ?? 0,
                'currency_id' => $payment['currency_id'] ?? 'ARS',
                'date_created' => $payment['date_created'] ?? null,
                'date_approved' => $payment['date_approved'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Error processing MercadoPago webhook payment', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}