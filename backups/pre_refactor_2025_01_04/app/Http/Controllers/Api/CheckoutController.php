<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Coupon;
use App\Models\Subscription;
use App\Models\PaymentMethod;
use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Inicializar proceso de checkout - validar sesión y datos del usuario
     */
    public function initialize(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Verificar si el usuario ya tiene una suscripción activa
        $activeSubscription = $user->subscriptions()
            ->whereIn('status', ['active', 'in_trial'])
            ->first();

        if ($activeSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'Ya tienes una suscripción activa',
                'data' => [
                    'has_active_subscription' => true,
                    'current_plan' => $activeSubscription->plan->name,
                    'subscription_status' => $activeSubscription->status,
                ],
            ], 400);
        }

        // Obtener datos del usuario desde sus perfiles
        $adminProfile = $user->adminProfiles()->first();
        $waiterProfile = $user->waiterProfiles()->first();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? $adminProfile->corporate_phone ?? $waiterProfile->phone,
                ],
                'billing_info' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? $adminProfile->corporate_phone ?? $waiterProfile->phone,
                    'company_name' => $adminProfile->business_name ?? null,
                    'address' => null, // TODO: agregar campos de dirección a los perfiles
                    'city' => null,
                    'state' => null,
                    'zip_code' => null,
                    'country' => 'ARG',
                ],
                'has_active_subscription' => false,
            ],
        ]);
    }

    /**
     * Crear una nueva suscripción y procesar el pago
     */
    public function createSubscription(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'payment_method_slug' => 'required|exists:payment_methods,slug',
            'currency' => 'sometimes|string|in:ARS,USD',
            'billing_period' => 'sometimes|string|in:monthly,quarterly,yearly',
            'coupon_code' => 'sometimes|string',
            'customer_data' => 'required|array',
            'customer_data.name' => 'required|string|max:255',
            'customer_data.email' => 'required|email',
            'customer_data.phone' => 'sometimes|string|max:20',
            'customer_data.address' => 'sometimes|string|max:500',
            'customer_data.city' => 'sometimes|string|max:100',
            'customer_data.state' => 'sometimes|string|max:100',
            'customer_data.zip_code' => 'sometimes|string|max:20',
            'customer_data.cuit' => 'sometimes|string|max:13',
        ]);

        $user = Auth::user();
        $plan = Plan::findOrFail($request->plan_id);
        $paymentMethod = PaymentMethod::where('slug', $request->payment_method_slug)
            ->where('is_enabled', true)
            ->firstOrFail();

        // Verificar que no tenga suscripción activa
        $activeSubscription = $user->subscriptions()
            ->whereIn('status', ['active', 'in_trial'])
            ->first();

        if ($activeSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'Ya tienes una suscripción activa',
            ], 400);
        }

        $currency = $request->get('currency', 'ARS');
        $billingPeriod = $request->get('billing_period', $plan->billing_period);
        $couponCode = $request->get('coupon_code');

        // Validar cupón si se proporciona
        $coupon = null;
        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)
                ->where('is_active', true)
                ->first();

            if (!$coupon || !$coupon->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cupón inválido o expirado',
                ], 400);
            }
        }

        try {
            return DB::transaction(function () use ($user, $plan, $paymentMethod, $currency, $billingPeriod, $coupon, $request) {

                // Calcular precios
                $basePrice = $plan->getPriceWithDiscount($billingPeriod, $currency);
                $finalPrice = $coupon ? $plan->getDiscountedPrice($coupon, $currency) : $basePrice;

                // Calcular impuestos
                $taxes = 0;
                if ($plan->tax_percentage > 0 && !$plan->tax_inclusive) {
                    $taxes = $finalPrice * ($plan->tax_percentage / 100);
                }

                $totalAmount = $finalPrice + $taxes;

                // Determinar si inicia en trial
                $isTrialStart = $plan->hasTrialEnabled() && (!$plan->trial_requires_payment_method || $totalAmount == 0);

                // Crear suscripción
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'provider' => $paymentMethod->slug,
                    'status' => $isTrialStart ? 'in_trial' : 'pending',
                    'auto_renew' => true,
                    'current_period_end' => $this->calculatePeriodEnd($billingPeriod),
                    'trial_ends_at' => $isTrialStart ? now()->addDays($plan->getTrialDays()) : null,
                    'coupon_id' => $coupon?->id,
                    'metadata' => [
                        'billing_period' => $billingPeriod,
                        'currency' => $currency,
                        'original_price' => $basePrice,
                        'final_price' => $finalPrice,
                        'taxes' => $taxes,
                        'total_amount' => $totalAmount,
                    ],
                ]);

                // Crear factura
                $invoice = $this->createInvoice($subscription, $request->customer_data, $totalAmount, $taxes, $currency);

                // Si no hay monto a pagar (trial gratuito o cupón 100% descuento)
                if ($totalAmount <= 0) {
                    $invoice->markAsPaid();

                    if ($coupon) {
                        $coupon->redeem();
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'Suscripción creada exitosamente',
                        'data' => [
                            'subscription_id' => $subscription->id,
                            'status' => $subscription->status,
                            'requires_payment' => false,
                            'trial_days' => $plan->getTrialDays(),
                            'invoice_id' => $invoice->id,
                        ],
                    ]);
                }

                // Crear transacción pendiente
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'invoice_id' => $invoice->id,
                    'payment_method_id' => $paymentMethod->id,
                    'amount' => $totalAmount,
                    'amount_cents' => (int)($totalAmount * 100),
                    'currency' => $currency,
                    'status' => 'pending',
                    'type' => 'payment',
                    'customer_email' => $request->customer_data['email'],
                    'customer_data' => $request->customer_data,
                    'description' => "Suscripción a plan {$plan->name} - {$billingPeriod}",
                ]);

                // Procesar pago según el método
                $paymentResult = $this->processPayment($paymentMethod, $transaction, $request->customer_data);

                if (!$paymentResult['success']) {
                    $transaction->markAsFailed($paymentResult['message']);

                    return response()->json([
                        'success' => false,
                        'message' => $paymentResult['message'],
                    ], 400);
                }

                // Redimir cupón si el pago fue exitoso
                if ($coupon && $paymentResult['success']) {
                    $coupon->redeem();
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Procesando pago...',
                    'data' => [
                        'subscription_id' => $subscription->id,
                        'transaction_id' => $transaction->id,
                        'invoice_id' => $invoice->id,
                        'payment_data' => $paymentResult['data'],
                        'requires_payment' => true,
                    ],
                ]);
            });

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la suscripción: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verificar estado de una transacción
     */
    public function checkTransactionStatus(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'transaction_id' => $transaction->id,
                'status' => $transaction->status,
                'amount' => $transaction->getFormattedAmount(),
                'currency' => $transaction->currency,
                'payment_method' => $transaction->paymentMethod->name,
                'created_at' => $transaction->created_at,
                'processed_at' => $transaction->processed_at,
                'failure_reason' => $transaction->failure_reason,
                'subscription' => [
                    'id' => $transaction->subscription->id,
                    'status' => $transaction->subscription->status,
                    'plan_name' => $transaction->subscription->plan->name,
                ],
            ],
        ]);
    }

    private function calculatePeriodEnd(string $billingPeriod): \Carbon\Carbon
    {
        return match($billingPeriod) {
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            'yearly' => now()->addYear(),
            default => now()->addMonth(),
        };
    }

    private function createInvoice(Subscription $subscription, array $customerData, float $totalAmount, float $taxes, string $currency): Invoice
    {
        $pointOfSale = '0001';
        $invoiceNumber = Invoice::generateNextInvoiceNumber($pointOfSale);

        return Invoice::create([
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'point_of_sale' => $pointOfSale,
            'invoice_number' => $invoiceNumber,
            'full_number' => Invoice::generateFullNumber($pointOfSale, $invoiceNumber),
            'invoice_type' => 'B', // Tipo B por defecto
            'customer_name' => $customerData['name'],
            'customer_email' => $customerData['email'],
            'customer_cuit' => $customerData['cuit'] ?? null,
            'customer_address' => $customerData['address'] ?? null,
            'customer_city' => $customerData['city'] ?? null,
            'customer_state' => $customerData['state'] ?? null,
            'customer_zip_code' => $customerData['zip_code'] ?? null,
            'company_name' => config('app.company_name', 'Tu Empresa SRL'),
            'company_cuit' => config('app.company_cuit', '20-12345678-9'),
            'company_address' => config('app.company_address', 'Dirección de la empresa'),
            'company_city' => config('app.company_city', 'Buenos Aires'),
            'company_state' => config('app.company_state', 'CABA'),
            'company_zip_code' => config('app.company_zip_code', '1000'),
            'line_items' => [
                [
                    'description' => "Suscripción a plan {$subscription->plan->name}",
                    'quantity' => 1,
                    'unit_price' => $totalAmount - $taxes,
                    'total' => $totalAmount - $taxes,
                ]
            ],
            'description' => "Suscripción a plan {$subscription->plan->name}",
            'subtotal_cents' => (int)(($totalAmount - $taxes) * 100),
            'tax_cents' => (int)($taxes * 100),
            'total_cents' => (int)($totalAmount * 100),
            'currency' => $currency,
            'tax_percentage' => $subscription->plan->tax_percentage,
            'status' => 'draft',
            'due_date' => now()->addDays(30),
        ]);
    }

    private function processPayment(PaymentMethod $paymentMethod, Transaction $transaction, array $customerData): array
    {
        // TODO: Implementar procesamiento real según el payment method
        // Por ahora retorna éxito simulado para MercadoPago

        if ($paymentMethod->isMercadoPago()) {
            return $this->processMercadoPagoPayment($transaction, $customerData);
        }

        if ($paymentMethod->isPayPal()) {
            return $this->processPayPalPayment($transaction, $customerData);
        }

        if ($paymentMethod->isStripe()) {
            return $this->processStripePayment($transaction, $customerData);
        }

        return [
            'success' => false,
            'message' => 'Método de pago no implementado',
        ];
    }

    private function processMercadoPagoPayment(Transaction $transaction, array $customerData): array
    {
        // TODO: Implementar integración real con MercadoPago
        // Por ahora simulamos una respuesta exitosa

        $transaction->update([
            'gateway_transaction_id' => 'mp_' . time() . '_' . rand(1000, 9999),
            'gateway_order_id' => 'order_' . time(),
            'status' => 'processing',
            'gateway_metadata' => [
                'init_point' => 'https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=123456789',
                'payment_id' => null,
            ],
        ]);

        return [
            'success' => true,
            'data' => [
                'payment_url' => 'https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=123456789',
                'transaction_id' => $transaction->id,
                'gateway_transaction_id' => $transaction->gateway_transaction_id,
            ],
        ];
    }

    private function processPayPalPayment(Transaction $transaction, array $customerData): array
    {
        // TODO: Implementar PayPal
        return [
            'success' => false,
            'message' => 'PayPal no implementado aún',
        ];
    }

    private function processStripePayment(Transaction $transaction, array $customerData): array
    {
        // TODO: Implementar Stripe
        return [
            'success' => false,
            'message' => 'Stripe no implementado aún',
        ];
    }
}