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

        // Log para debugging
        if (!$this->accessToken) {
            Log::warning('MercadoPagoService: Access token no configurado', [
                'config_value' => config('services.mercado_pago.access_token'),
                'env_value' => env('MERCADO_PAGO_ACCESS_TOKEN') ? 'exists' : 'missing',
            ]);
        }
    }

    public function createPreference(array $data)
    {
        // Validar que el access token esté configurado
        if (!$this->accessToken) {
            Log::error('MercadoPagoService: Cannot create preference - access token not configured');
            throw new \Exception('MercadoPago no está configurado correctamente. Por favor verifica las credenciales.');
        }

        // Validar que el precio sea mayor a 0
        if (!isset($data['unit_price']) || $data['unit_price'] <= 0) {
            Log::error('MercadoPagoService: Invalid price - must be greater than 0', [
                'unit_price' => $data['unit_price'] ?? 'not set',
                'data' => $data,
            ]);
            throw new \Exception('El precio debe ser mayor a cero. Por favor verifica la configuración del plan.');
        }

        Log::info('MercadoPagoService: Starting createPreference', [
            'access_token_length' => strlen($this->accessToken),
            'access_token_start' => substr($this->accessToken, 0, 10) . '...',
            'base_url' => $this->baseUrl,
            'input_data' => $data,
        ]);

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

        Log::info('MercadoPagoService: Sending request to MercadoPago', [
            'url' => $this->baseUrl . '/checkout/preferences',
            'preference_data' => $preferenceData,
            'headers' => [
                'Authorization' => 'Bearer ' . substr($this->accessToken, 0, 10) . '...',
                'Content-Type' => 'application/json',
            ],
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/checkout/preferences', $preferenceData);

        Log::info('MercadoPagoService: Received response from MercadoPago', [
            'status_code' => $response->status(),
            'successful' => $response->successful(),
            'response_body' => $response->body(),
            'response_headers' => $response->headers(),
        ]);

        if ($response->successful()) {
            $responseData = $response->json();
            Log::info('MercadoPagoService: Successfully created preference', [
                'preference_id' => $responseData['id'] ?? 'no_id',
                'init_point' => $responseData['init_point'] ?? 'no_init_point',
            ]);
            return $responseData;
        }

        Log::error('MercadoPagoService: Error creating preference', [
            'status' => $response->status(),
            'response' => $response->body(),
            'data' => $preferenceData,
            'access_token_configured' => !empty($this->accessToken),
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