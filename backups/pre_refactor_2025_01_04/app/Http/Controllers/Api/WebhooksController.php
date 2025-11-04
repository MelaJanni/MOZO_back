<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WebhooksController extends Controller
{
    /**
     * Webhook de MercadoPago
     */
    public function mercadopago(Request $request): JsonResponse
    {
        Log::info('MercadoPago Webhook received', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'ip' => $request->ip(),
        ]);

        try {
            $paymentMethod = PaymentMethod::where('slug', 'mercadopago')->first();

            if (!$paymentMethod) {
                Log::error('MercadoPago payment method not found');
                return response()->json(['error' => 'Payment method not found'], 404);
            }

            // Validar webhook signature si está configurada
            if (!$this->validateMercadoPagoWebhook($request, $paymentMethod)) {
                Log::warning('Invalid MercadoPago webhook signature');
                return response()->json(['error' => 'Invalid signature'], 403);
            }

            $type = $request->input('type');
            $data = $request->input('data');

            Log::info('Processing MercadoPago webhook', [
                'type' => $type,
                'data' => $data,
            ]);

            switch ($type) {
                case 'payment':
                    return $this->handleMercadoPagoPayment($data, $request->all());

                case 'subscription':
                    return $this->handleMercadoPagoSubscription($data, $request->all());

                default:
                    Log::info('Unhandled MercadoPago webhook type', ['type' => $type]);
                    return response()->json(['status' => 'ok'], 200);
            }

        } catch (\Exception $e) {
            Log::error('Error processing MercadoPago webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Webhook de PayPal
     */
    public function paypal(Request $request): JsonResponse
    {
        Log::info('PayPal Webhook received', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        // TODO: Implementar webhooks de PayPal
        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Webhook de Stripe
     */
    public function stripe(Request $request): JsonResponse
    {
        Log::info('Stripe Webhook received', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        // TODO: Implementar webhooks de Stripe
        return response()->json(['status' => 'ok'], 200);
    }

    private function validateMercadoPagoWebhook(Request $request, PaymentMethod $paymentMethod): bool
    {
        $webhookSecret = $paymentMethod->webhook_secret;

        if (!$webhookSecret) {
            return true; // Si no hay secret configurado, asumimos válido
        }

        // TODO: Implementar validación real de signature de MercadoPago
        // Por ahora retornamos true
        return true;
    }

    private function handleMercadoPagoPayment(array $data, array $fullWebhook): JsonResponse
    {
        $paymentId = $data['id'] ?? null;

        if (!$paymentId) {
            Log::warning('MercadoPago payment webhook without payment ID');
            return response()->json(['error' => 'Missing payment ID'], 400);
        }

        try {
            return DB::transaction(function () use ($paymentId, $fullWebhook) {

                // Buscar la transacción por gateway_transaction_id
                $transaction = Transaction::where('gateway_transaction_id', 'like', '%' . $paymentId . '%')
                    ->orWhere('gateway_metadata->payment_id', $paymentId)
                    ->first();

                if (!$transaction) {
                    Log::warning('Transaction not found for MercadoPago payment', [
                        'payment_id' => $paymentId,
                    ]);
                    return response()->json(['error' => 'Transaction not found'], 404);
                }

                // Registrar webhook recibido
                $transaction->addWebhookReceived($fullWebhook);

                // TODO: Consultar API de MercadoPago para obtener detalles del pago
                // Por ahora simulamos una respuesta exitosa
                $paymentStatus = $this->getMercadoPagoPaymentStatus($paymentId);

                Log::info('MercadoPago payment status', [
                    'transaction_id' => $transaction->id,
                    'payment_id' => $paymentId,
                    'status' => $paymentStatus,
                ]);

                switch ($paymentStatus) {
                    case 'approved':
                        $this->handleSuccessfulPayment($transaction, $paymentId);
                        break;

                    case 'rejected':
                    case 'cancelled':
                        $this->handleFailedPayment($transaction, $paymentStatus);
                        break;

                    case 'pending':
                    case 'in_process':
                        // Mantener como processing
                        $transaction->markAsProcessing();
                        break;

                    case 'refunded':
                        $this->handleRefundedPayment($transaction);
                        break;

                    default:
                        Log::warning('Unknown MercadoPago payment status', [
                            'status' => $paymentStatus,
                            'transaction_id' => $transaction->id,
                        ]);
                }

                return response()->json(['status' => 'ok'], 200);
            });

        } catch (\Exception $e) {
            Log::error('Error handling MercadoPago payment webhook', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Processing error'], 500);
        }
    }

    private function handleMercadoPagoSubscription(array $data, array $fullWebhook): JsonResponse
    {
        // TODO: Implementar manejo de webhooks de suscripciones de MercadoPago
        Log::info('MercadoPago subscription webhook received', $data);

        return response()->json(['status' => 'ok'], 200);
    }

    private function getMercadoPagoPaymentStatus(string $paymentId): string
    {
        // TODO: Implementar consulta real a la API de MercadoPago
        // Por ahora simulamos estados aleatorios para testing

        $statuses = ['approved', 'rejected', 'pending', 'cancelled'];
        return $statuses[array_rand($statuses)];
    }

    private function handleSuccessfulPayment(Transaction $transaction, string $paymentId): void
    {
        if ($transaction->isCompleted()) {
            Log::info('Transaction already completed', [
                'transaction_id' => $transaction->id,
                'payment_id' => $paymentId,
            ]);
            return;
        }

        // Marcar transacción como completada
        $transaction->update([
            'status' => 'completed',
            'processed_at' => now(),
            'gateway_transaction_id' => $paymentId,
            'gateway_metadata' => array_merge(
                $transaction->gateway_metadata ?? [],
                ['payment_id' => $paymentId, 'status' => 'approved']
            ),
        ]);

        // Activar suscripción
        if ($transaction->subscription) {
            $subscription = $transaction->subscription;

            if ($subscription->status === 'pending') {
                $subscription->update(['status' => 'active']);
                Log::info('Subscription activated', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                ]);
            }
        }

        // Marcar factura como pagada
        if ($transaction->invoice) {
            $transaction->invoice->markAsPaid();
            Log::info('Invoice marked as paid', [
                'invoice_id' => $transaction->invoice->id,
            ]);
        }

        Log::info('Payment processed successfully', [
            'transaction_id' => $transaction->id,
            'payment_id' => $paymentId,
            'amount' => $transaction->getFormattedAmount(),
        ]);
    }

    private function handleFailedPayment(Transaction $transaction, string $status): void
    {
        if ($transaction->isFailed()) {
            Log::info('Transaction already failed', [
                'transaction_id' => $transaction->id,
                'status' => $status,
            ]);
            return;
        }

        $reason = match($status) {
            'rejected' => 'Pago rechazado por el procesador',
            'cancelled' => 'Pago cancelado por el usuario',
            default => 'Pago fallido: ' . $status,
        };

        $transaction->markAsFailed($reason);

        // Cancelar suscripción si estaba pendiente
        if ($transaction->subscription && $transaction->subscription->status === 'pending') {
            $transaction->subscription->update(['status' => 'canceled']);
            Log::info('Subscription cancelled due to failed payment', [
                'subscription_id' => $transaction->subscription->id,
            ]);
        }

        // Cancelar factura
        if ($transaction->invoice && $transaction->invoice->status === 'draft') {
            $transaction->invoice->cancel();
            Log::info('Invoice cancelled due to failed payment', [
                'invoice_id' => $transaction->invoice->id,
            ]);
        }

        Log::warning('Payment failed', [
            'transaction_id' => $transaction->id,
            'status' => $status,
            'reason' => $reason,
        ]);
    }

    private function handleRefundedPayment(Transaction $transaction): void
    {
        if ($transaction->isRefunded()) {
            Log::info('Transaction already refunded', [
                'transaction_id' => $transaction->id,
            ]);
            return;
        }

        $transaction->markAsRefunded();

        // Suspender suscripción
        if ($transaction->subscription && $transaction->subscription->isActive()) {
            $transaction->subscription->update(['status' => 'canceled']);
            Log::info('Subscription cancelled due to refund', [
                'subscription_id' => $transaction->subscription->id,
            ]);
        }

        Log::info('Payment refunded', [
            'transaction_id' => $transaction->id,
        ]);
    }
}