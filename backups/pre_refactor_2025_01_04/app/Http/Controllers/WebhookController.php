<?php

namespace App\Http\Controllers;

use App\Models\WebhookLog;
use App\Services\PaymentProviderManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    protected $paymentManager;

    public function __construct(PaymentProviderManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    public function mercadopago(Request $request)
    {
        try {
            Log::info('MercadoPago webhook received', [
                'headers' => $request->headers->all(),
                'payload' => $request->all()
            ]);

            $payload = $request->all();

            // Crear log del webhook
            $webhookLog = WebhookLog::create([
                'provider' => 'mercado_pago',
                'event_type' => $payload['type'] ?? null,
                'external_id' => $payload['data']['id'] ?? null,
                'payload' => $payload,
                'status' => 'received',
            ]);

            // Verificar que es una notificación de pago
            if ($request->get('type') !== 'payment') {
                $webhookLog->update(['status' => 'ignored']);
                Log::info('MercadoPago webhook: not a payment notification', [
                    'type' => $request->get('type')
                ]);
                return response()->json(['status' => 'ignored'], 200);
            }

            $provider = $this->paymentManager->getProvider('mercado_pago');
            $result = $provider->handleWebhook($payload);

            if ($result['success']) {
                $webhookLog->update([
                    'status' => 'processed',
                    'processed_at' => now()
                ]);

                Log::info('MercadoPago webhook processed successfully', [
                    'payment_id' => $result['payment_id'] ?? null,
                    'status' => $result['status'] ?? null
                ]);
                return response()->json(['status' => 'success'], 200);
            } else {
                $webhookLog->update([
                    'status' => 'failed',
                    'error_message' => $result['error'] ?? 'Unknown error'
                ]);

                Log::warning('MercadoPago webhook processing failed', [
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                return response()->json(['status' => 'error', 'message' => $result['error']], 400);
            }

        } catch (\Exception $e) {
            if (isset($webhookLog)) {
                $webhookLog->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }

            Log::error('MercadoPago webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    public function paypal(Request $request)
    {
        try {
            Log::info('PayPal webhook received', [
                'headers' => $request->headers->all(),
                'payload' => $request->all()
            ]);

            $payload = $request->all();

            // Crear log del webhook
            $webhookLog = WebhookLog::create([
                'provider' => 'paypal',
                'event_type' => $payload['event_type'] ?? null,
                'external_id' => $payload['id'] ?? null,
                'payload' => $payload,
                'status' => 'received',
            ]);

            // Verificar el webhook de PayPal (firma y origen)
            $isValid = $this->verifyPayPalWebhook($request);
            if (!$isValid) {
                $webhookLog->update([
                    'status' => 'failed',
                    'error_message' => 'Webhook verification failed'
                ]);

                Log::warning('PayPal webhook verification failed');
                return response()->json(['status' => 'unauthorized'], 401);
            }

            $provider = $this->paymentManager->getProvider('paypal');
            $result = $provider->handleWebhook($payload);

            if ($result['success']) {
                $webhookLog->update([
                    'status' => 'processed',
                    'processed_at' => now()
                ]);

                Log::info('PayPal webhook processed successfully', [
                    'event_type' => $request->get('event_type'),
                    'resource_id' => $result['resource_id'] ?? null
                ]);
                return response()->json(['status' => 'success'], 200);
            } else {
                $webhookLog->update([
                    'status' => 'failed',
                    'error_message' => $result['error'] ?? 'Unknown error'
                ]);

                Log::warning('PayPal webhook processing failed', [
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                return response()->json(['status' => 'error', 'message' => $result['error']], 400);
            }

        } catch (\Exception $e) {
            if (isset($webhookLog)) {
                $webhookLog->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }

            Log::error('PayPal webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    public function bankTransfer(Request $request)
    {
        try {
            Log::info('Bank transfer webhook received', [
                'payload' => $request->all()
            ]);

            $payload = $request->all();

            // Crear log del webhook
            $webhookLog = WebhookLog::create([
                'provider' => 'bank_transfer',
                'event_type' => 'manual_confirmation',
                'external_id' => $payload['subscription_id'] ?? null,
                'payload' => $payload,
                'status' => 'received',
            ]);

            // Este webhook es principalmente para uso interno o cuando se confirma manualmente
            $provider = $this->paymentManager->getProvider('bank_transfer');
            $result = $provider->handleWebhook($payload);

            if ($result['success']) {
                $webhookLog->update([
                    'status' => 'processed',
                    'processed_at' => now()
                ]);

                Log::info('Bank transfer webhook processed successfully');
                return response()->json(['status' => 'success'], 200);
            } else {
                $webhookLog->update([
                    'status' => 'failed',
                    'error_message' => $result['error'] ?? 'Unknown error'
                ]);

                Log::warning('Bank transfer webhook processing failed', [
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                return response()->json(['status' => 'error', 'message' => $result['error']], 400);
            }

        } catch (\Exception $e) {
            if (isset($webhookLog)) {
                $webhookLog->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }

            Log::error('Bank transfer webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    protected function verifyPayPalWebhook(Request $request): bool
    {
        // Implementar verificación de webhook de PayPal
        // En producción, se debe verificar la firma del webhook
        $webhookId = config('services.paypal.webhook_id');

        if (!$webhookId) {
            Log::warning('PayPal webhook ID not configured');
            return false;
        }

        // PayPal envía headers específicos para verificación
        $paypalAuthAlgo = $request->header('PAYPAL-AUTH-ALGO');
        $paypalTransmissionId = $request->header('PAYPAL-TRANSMISSION-ID');
        $paypalCertId = $request->header('PAYPAL-CERT-ID');
        $paypalTransmissionSig = $request->header('PAYPAL-TRANSMISSION-SIG');
        $paypalTransmissionTime = $request->header('PAYPAL-TRANSMISSION-TIME');

        if (!$paypalAuthAlgo || !$paypalTransmissionId || !$paypalCertId ||
            !$paypalTransmissionSig || !$paypalTransmissionTime) {
            Log::warning('PayPal webhook missing required headers');
            return false;
        }

        // En un entorno de producción, aquí se verificaría la firma
        // Por ahora retornamos true para desarrollo
        return true;
    }

    public function test(Request $request)
    {
        return response()->json([
            'status' => 'webhook_endpoint_working',
            'timestamp' => now()->toIso8601String(),
            'payload_received' => $request->all()
        ]);
    }
}
