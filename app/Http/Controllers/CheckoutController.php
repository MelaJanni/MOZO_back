<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Coupon;
use App\Services\PaymentProviderManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    protected $paymentManager;

    public function __construct(PaymentProviderManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    public function index()
    {
        $plans = Plan::where('is_active', true)
                    ->orderBy('price')
                    ->get();

        return view('checkout.index', compact('plans'));
    }

    public function plan($planId)
    {
        $plan = Plan::where('id', $planId)
                   ->where('is_active', true)
                   ->firstOrFail();

        $user = Auth::user();
        $paymentMethods = ['mercado_pago', 'paypal', 'bank_transfer'];

        return view('checkout.plan', compact('plan', 'user', 'paymentMethods'));
    }

    public function applyCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'plan_id' => 'required|exists:plans,id'
        ]);

        $coupon = Coupon::where('code', $request->code)
                        ->where('is_active', true)
                        ->first();

        if (!$coupon || !$coupon->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Cupón inválido o expirado'
            ]);
        }

        $plan = Plan::findOrFail($request->plan_id);

        if (!$coupon->isApplicableToPlan($plan)) {
            return response()->json([
                'success' => false,
                'message' => 'Cupón no aplicable a este plan'
            ]);
        }

        $discount = $coupon->calculateDiscount($plan->price);
        $finalPrice = $plan->price - $discount;

        return response()->json([
            'success' => true,
            'discount' => $discount,
            'final_price' => $finalPrice,
            'coupon' => [
                'code' => $coupon->code,
                'description' => $coupon->description,
                'type' => $coupon->type,
                'value' => $coupon->value
            ]
        ]);
    }

    public function processPayment(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'payment_method' => 'required|in:mercado_pago,paypal,bank_transfer',
            'coupon_code' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $plan = Plan::findOrFail($request->plan_id);
            $user = Auth::user();

            // Aplicar cupón si existe
            $coupon = null;
            $discount = 0;
            if ($request->coupon_code) {
                $coupon = Coupon::where('code', $request->coupon_code)
                                ->where('is_active', true)
                                ->first();

                if ($coupon && $coupon->isValid() && $coupon->isApplicableToPlan($plan)) {
                    $discount = $coupon->calculateDiscount($plan->price);
                }
            }

            $finalPrice = $plan->price - $discount;

            // Crear la suscripción pendiente
            $subscription = $user->subscriptions()->create([
                'plan_id' => $plan->id,
                'status' => 'pending',
                'current_period_start' => now(),
                'current_period_end' => now()->addDays($plan->billing_interval_days),
                'price' => $finalPrice,
                'currency' => 'ARS',
                'metadata' => [
                    'original_price' => $plan->price,
                    'discount_applied' => $discount,
                    'coupon_code' => $coupon?->code
                ]
            ]);

            // Procesar el pago según el método
            $provider = $this->paymentManager->getProvider($request->payment_method);

            $checkoutData = $provider->createCheckout([
                'subscription_id' => $subscription->id,
                'plan' => $plan,
                'user' => $user,
                'amount' => $finalPrice,
                'currency' => 'ARS',
                'success_url' => route('checkout.success'),
                'cancel_url' => route('checkout.cancel'),
                'metadata' => [
                    'subscription_id' => $subscription->id,
                    'plan_id' => $plan->id,
                    'user_id' => $user->id
                ]
            ]);

            // Guardar información del checkout
            $subscription->update([
                'external_id' => $checkoutData['external_id'] ?? null,
                'metadata' => array_merge($subscription->metadata ?? [], [
                    'checkout_data' => $checkoutData
                ])
            ]);

            // Usar cupón si se aplicó
            if ($coupon) {
                $coupon->use();
            }

            DB::commit();

            // Redirigir según el método de pago
            if ($request->payment_method === 'bank_transfer') {
                return redirect()->route('checkout.bank-transfer', $subscription->id);
            }

            return redirect($checkoutData['checkout_url']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error procesando pago: ' . $e->getMessage());

            return back()->withErrors(['error' => 'Error procesando el pago. Intenta nuevamente.']);
        }
    }

    public function bankTransfer($subscriptionId)
    {
        $subscription = Auth::user()->subscriptions()
                           ->where('id', $subscriptionId)
                           ->where('status', 'pending')
                           ->firstOrFail();

        $plan = $subscription->plan;

        return view('checkout.bank-transfer', compact('subscription', 'plan'));
    }

    public function success(Request $request)
    {
        $user = Auth::user();
        $subscription = $user->subscriptions()
                           ->where('status', 'active')
                           ->latest()
                           ->first();

        return view('checkout.success', compact('subscription'));
    }

    public function cancel(Request $request)
    {
        return view('checkout.cancel');
    }
}