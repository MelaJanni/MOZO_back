<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Subscription;
use App\Models\PaymentMethod;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PublicCheckoutController extends Controller
{
    protected $mercadoPagoService;

    public function __construct(MercadoPagoService $mercadoPagoService)
    {
        $this->mercadoPagoService = $mercadoPagoService;
    }

    public function index()
    {
        $plans = Plan::active()->ordered()->get();
        return view('public.checkout.filament-index', compact('plans'));
    }

    public function plan(Plan $plan)
    {
        if (!$plan->is_active) {
            abort(404);
        }

        $paymentMethods = PaymentMethod::active()->ordered()->get();
        $user = Auth::user(); // Detectar si hay usuario autenticado

        Log::info('Plan checkout page accessed', [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'user_authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'user_email' => $user ? $user->email : null,
        ]);

        return view('public.checkout.filament-plan', compact('plan', 'paymentMethods', 'user'));
    }

    public function subscribe(Request $request)
    {
        Log::info('🔵 Subscribe method called', [
            'user_id' => auth()->id(),
            'request_data' => $request->all(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        try {
            $request->validate([
                'plan_id' => 'required|exists:plans,id',
                'billing_period' => 'required|in:monthly,quarterly,yearly',
                'payment_method' => 'required|in:mercadopago,bank_transfer',
                'coupon_code' => 'nullable|string',
                'terms' => 'required|accepted',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Validation failed in subscribe', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);
            throw $e;
        }

        $plan = Plan::findOrFail($request->plan_id);
        $user = auth()->user();

        if (!$plan->is_active) {
            return back()->withErrors(['plan_id' => 'El plan seleccionado no está disponible.']);
        }

        DB::beginTransaction();

        try {
            // Aplicar cupón si existe
            $coupon = null;
            if ($request->coupon_code) {
                $coupon = Coupon::where('code', $request->coupon_code)
                    ->where('is_active', true)
                    ->where('starts_at', '<=', now())
                    ->where('expires_at', '>=', now())
                    ->first();

                if (!$coupon || !$coupon->isValid()) {
                    return back()->withErrors(['coupon_code' => 'El cupón no es válido o ha expirado.']);
                }
            }

            // Calcular precio
            Log::info('Price calculation debug', [
                'plan_id' => $plan->id,
                'billing_period' => $request->billing_period,
                'plan_prices_raw' => $plan->prices,
                'plan_default_currency' => $plan->default_currency,
            ]);

            $basePrice = $plan->getPriceWithDiscount($request->billing_period);
            Log::info('Base price calculated', [
                'base_price' => $basePrice,
                'base_price_type' => gettype($basePrice),
            ]);

            $finalPrice = $coupon ? $plan->getDiscountedPrice($coupon) : $basePrice;
            Log::info('Final price calculated', [
                'final_price' => $finalPrice,
                'final_price_type' => gettype($finalPrice),
                'has_coupon' => !is_null($coupon),
            ]);

            // Crear suscripción
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'pending',
                'billing_period' => $request->billing_period,
                'price_at_creation' => $finalPrice,
                'currency' => 'ARS',
                'trial_ends_at' => $plan->hasTrialEnabled() ? now()->addDays($plan->getTrialDays()) : null,
                'next_billing_date' => $this->calculateNextBillingDate($request->billing_period, $plan->hasTrialEnabled() ? $plan->getTrialDays() : 0),
                'coupon_id' => $coupon?->id,
                'metadata' => [
                    'user_ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);

            // Usar cupón si existe
            if ($coupon) {
                $coupon->increment('redeemed_count');
            }

            // Procesar pago según método
            if ($request->payment_method === 'mercadopago') {
                Log::info('🔵 START: Processing MercadoPago payment', [
                    'subscription_id' => $subscription->id,
                    'price' => $subscription->price_at_creation,
                    'user_id' => $user->id,
                ]);

                $paymentResult = $this->processMercadoPago($subscription, $request);

                Log::info('🔵 RESULT: MercadoPago payment processed', [
                    'success' => $paymentResult['success'] ?? false,
                    'has_checkout_url' => isset($paymentResult['checkout_url']),
                    'has_message' => isset($paymentResult['message']),
                    'full_result' => $paymentResult,
                ]);

                if (!$paymentResult['success']) {
                    DB::rollBack();
                    Log::error('❌ FAIL: MercadoPago payment failed', [
                        'message' => $paymentResult['message'] ?? 'No message provided',
                        'subscription_id' => $subscription->id,
                        'full_result' => $paymentResult,
                    ]);
                    return back()->withErrors(['payment' => $paymentResult['message'] ?? 'Error desconocido']);
                }

                DB::commit();
                Log::info('✅ SUCCESS: Redirecting to MercadoPago', [
                    'checkout_url' => $paymentResult['checkout_url'],
                ]);
                return redirect($paymentResult['checkout_url']);
            }

            if ($request->payment_method === 'bank_transfer') {
                $subscription->update(['status' => 'pending_bank_transfer']);

                DB::commit();
                return redirect()->route('public.checkout.bank-transfer', $subscription->id);
            }

            DB::rollBack();
            return back()->withErrors(['payment_method' => 'Método de pago no válido.']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('💥 EXCEPTION in subscribe method', [
                'user_id' => $user->id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['general' => 'Ocurrió un error procesando tu suscripción. Intenta nuevamente.']);
        }
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'plan_id' => 'required|exists:plans,id',
            'billing_period' => 'required|in:monthly,quarterly,yearly',
            'payment_method' => 'required|in:mercadopago,bank_transfer',
            'coupon_code' => 'nullable|string',
            'terms' => 'required|accepted',
        ]);

        $plan = Plan::findOrFail($request->plan_id);

        if (!$plan->is_active) {
            return back()->withErrors(['plan_id' => 'El plan seleccionado no está disponible.']);
        }

        DB::beginTransaction();

        try {
            // Crear usuario
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'email_verified_at' => now(),
            ]);

            // Aplicar cupón si existe
            $coupon = null;
            if ($request->coupon_code) {
                $coupon = Coupon::where('code', $request->coupon_code)
                    ->where('is_active', true)
                    ->where('starts_at', '<=', now())
                    ->where('expires_at', '>=', now())
                    ->first();

                if (!$coupon || !$coupon->isValid()) {
                    return back()->withErrors(['coupon_code' => 'El cupón no es válido o ha expirado.']);
                }
            }

            // Calcular precio
            Log::info('Price calculation debug', [
                'plan_id' => $plan->id,
                'billing_period' => $request->billing_period,
                'plan_prices_raw' => $plan->prices,
                'plan_default_currency' => $plan->default_currency,
            ]);

            $basePrice = $plan->getPriceWithDiscount($request->billing_period);
            Log::info('Base price calculated', [
                'base_price' => $basePrice,
                'base_price_type' => gettype($basePrice),
            ]);

            $finalPrice = $coupon ? $plan->getDiscountedPrice($coupon) : $basePrice;
            Log::info('Final price calculated', [
                'final_price' => $finalPrice,
                'final_price_type' => gettype($finalPrice),
                'has_coupon' => !is_null($coupon),
            ]);

            // Crear suscripción
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'pending',
                'billing_period' => $request->billing_period,
                'price_at_creation' => $finalPrice,
                'currency' => 'ARS',
                'trial_ends_at' => $plan->hasTrialEnabled() ? now()->addDays($plan->getTrialDays()) : null,
                'next_billing_date' => $this->calculateNextBillingDate($request->billing_period, $plan->hasTrialEnabled() ? $plan->getTrialDays() : 0),
                'coupon_id' => $coupon?->id,
                'metadata' => [
                    'registration_ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);

            // Usar cupón si existe
            if ($coupon) {
                $coupon->increment('redeemed_count');
            }

            // Procesar pago según método
            if ($request->payment_method === 'mercadopago') {
                $paymentResult = $this->processMercadoPago($subscription, $request);

                if (!$paymentResult['success']) {
                    DB::rollBack();
                    return back()->withErrors(['payment' => $paymentResult['message']]);
                }

                DB::commit();
                return redirect($paymentResult['checkout_url']);
            }

            if ($request->payment_method === 'bank_transfer') {
                $subscription->update(['status' => 'pending_bank_transfer']);

                DB::commit();
                Auth::login($user);

                return redirect()->route('public.checkout.bank-transfer', $subscription->id);
            }

            DB::rollBack();
            return back()->withErrors(['payment_method' => 'Método de pago no válido.']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en registro y checkout', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['general' => 'Ocurrió un error procesando tu registro. Intenta nuevamente.']);
        }
    }

    public function applyCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
            'plan_id' => 'required|exists:plans,id',
            'billing_period' => 'required|in:monthly,quarterly,yearly',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $coupon = Coupon::where('code', $request->coupon_code)
            ->where('is_active', true)
            ->first();

        if (!$coupon || !$coupon->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'El cupón no es válido o ha expirado.'
            ]);
        }

        $basePrice = $plan->getPriceWithDiscount($request->billing_period);
        $discountedPrice = $plan->getDiscountedPrice($coupon);
        $savings = $basePrice - $discountedPrice;

        return response()->json([
            'success' => true,
            'coupon' => [
                'code' => $coupon->code,
                'description' => $coupon->getDiscountDescription(),
                'type' => $coupon->type,
                'value' => $coupon->value,
            ],
            'pricing' => [
                'base_price' => $basePrice,
                'discounted_price' => $discountedPrice,
                'savings' => $savings,
                'formatted_base_price' => '$' . number_format($basePrice, 2),
                'formatted_discounted_price' => '$' . number_format($discountedPrice, 2),
                'formatted_savings' => '$' . number_format($savings, 2),
            ]
        ]);
    }

    public function bankTransfer(Subscription $subscription)
    {
        if ($subscription->status !== 'pending_bank_transfer') {
            abort(404);
        }

        $bankDetails = [
            'bank_name' => config('services.bank_transfer.bank_name'),
            'account_holder' => config('services.bank_transfer.account_holder'),
            'account_number' => config('services.bank_transfer.account_number'),
            'cbu' => config('services.bank_transfer.cbu'),
            'alias' => 'MOZO.QR.PAGOS',
        ];

        return view('public.checkout.bank-transfer', compact('subscription', 'bankDetails'));
    }

    public function success()
    {
        return view('public.checkout.filament-success');
    }

    public function cancel()
    {
        return view('public.checkout.filament-cancel');
    }

    public function gracePlan(Plan $plan)
    {
        if (!$plan->is_active) {
            abort(404);
        }

        $user = auth()->user();
        $subscription = $user->subscriptions()->latest()->first();

        // Verificar si el usuario realmente está en período de gracia
        if (!$subscription || !$subscription->requires_plan_selection) {
            return redirect()->route('public.plans.index');
        }

        $paymentMethods = PaymentMethod::active()->ordered()->get();

        return view('public.checkout.filament-grace-plan', compact('plan', 'paymentMethods', 'subscription'));
    }

    private function processMercadoPago(Subscription $subscription, Request $request)
    {
        Log::info('Processing MercadoPago payment', [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'amount' => $subscription->price_at_creation,
        ]);

        try {
            // Verificar que el servicio de MercadoPago esté disponible
            if (!$this->mercadoPagoService) {
                Log::error('MercadoPagoService not available');
                return [
                    'success' => false,
                    'message' => 'El servicio de pagos no está disponible en este momento. Por favor intenta más tarde.',
                ];
            }

            $preference = $this->mercadoPagoService->createPreference([
                'title' => "Suscripción {$subscription->plan->name}",
                'quantity' => 1,
                'unit_price' => $subscription->price_at_creation,
                'currency_id' => $subscription->currency,
                'external_reference' => $subscription->id,
                'payer' => [
                    'email' => $subscription->user->email,
                    'name' => $subscription->user->name,
                ],
                'back_urls' => [
                    'success' => route('public.checkout.success'),
                    'failure' => route('public.checkout.cancel'),
                    'pending' => route('public.checkout.success'),
                ],
                'auto_return' => 'approved',
                'notification_url' => route('webhooks.mercadopago'),
            ]);

            $subscription->update([
                'provider_subscription_id' => $preference['id'],
                'metadata' => array_merge($subscription->metadata ?? [], [
                    'mercadopago_preference_id' => $preference['id'],
                ])
            ]);

            Log::info('MercadoPago preference created successfully', [
                'preference_id' => $preference['id'],
                'checkout_url' => $preference['init_point'],
                'subscription_id' => $subscription->id,
            ]);

            return [
                'success' => true,
                'checkout_url' => $preference['init_point'],
            ];

        } catch (\Exception $e) {
            Log::error('Error creando preferencia MercadoPago', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Error procesando el pago. Por favor intenta nuevamente o contacta a soporte.',
            ];
        }
    }

    private function calculateNextBillingDate($period, $trialDays = 0)
    {
        $startDate = $trialDays > 0 ? now()->addDays($trialDays) : now();

        return match($period) {
            'monthly' => $startDate->addMonth(),
            'quarterly' => $startDate->addMonths(3),
            'yearly' => $startDate->addYear(),
            default => $startDate->addMonth(),
        };
    }
}