<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PlansController extends Controller
{
    /**
     * Obtener todos los planes activos para mostrar en la página pública
     */
    public function index(Request $request): JsonResponse
    {
        $currency = $request->get('currency', 'ARS');

        $plans = Plan::active()
            ->ordered()
            ->get()
            ->map(function ($plan) use ($currency) {
                return [
                    'id' => $plan->id,
                    'code' => $plan->code,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'billing_period' => $plan->billing_period,
                    'price' => $plan->getPrice($currency),
                    'formatted_price' => $plan->getFormattedPrice($currency),
                    'currency' => $currency,
                    'yearly_discount_percentage' => $plan->yearly_discount_percentage,
                    'quarterly_discount_percentage' => $plan->quarterly_discount_percentage,
                    'features' => $plan->features ?? [],
                    'limits' => [
                        'max_businesses' => $plan->getMaxBusinesses(),
                        'max_tables' => $plan->getMaxTables(),
                        'max_staff' => $plan->getMaxStaff(),
                    ],
                    'trial' => [
                        'enabled' => $plan->hasTrialEnabled(),
                        'days' => $plan->getTrialDays(),
                        'requires_payment_method' => $plan->trial_requires_payment_method,
                    ],
                    'is_featured' => $plan->is_featured,
                    'is_popular' => $plan->is_popular,
                    'sort_order' => $plan->sort_order,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $plans,
            'currency' => $currency,
        ]);
    }

    /**
     * Obtener un plan específico con todos sus detalles
     */
    public function show(Request $request, Plan $plan): JsonResponse
    {
        if (!$plan->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Plan no disponible',
            ], 404);
        }

        $currency = $request->get('currency', 'ARS');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $plan->id,
                'code' => $plan->code,
                'name' => $plan->name,
                'description' => $plan->description,
                'billing_period' => $plan->billing_period,
                'price' => $plan->getPrice($currency),
                'formatted_price' => $plan->getFormattedPrice($currency),
                'currency' => $currency,
                'pricing' => [
                    'monthly' => $plan->getPriceWithDiscount('monthly', $currency),
                    'quarterly' => $plan->getPriceWithDiscount('quarterly', $currency),
                    'yearly' => $plan->getPriceWithDiscount('yearly', $currency),
                ],
                'discounts' => [
                    'yearly_percentage' => $plan->yearly_discount_percentage,
                    'quarterly_percentage' => $plan->quarterly_discount_percentage,
                ],
                'features' => $plan->features ?? [],
                'limits' => $plan->limits ?? [],
                'limits_formatted' => [
                    'max_businesses' => $plan->getMaxBusinesses(),
                    'max_tables' => $plan->getMaxTables(),
                    'max_staff' => $plan->getMaxStaff(),
                ],
                'trial' => [
                    'enabled' => $plan->hasTrialEnabled(),
                    'days' => $plan->getTrialDays(),
                    'requires_payment_method' => $plan->trial_requires_payment_method,
                ],
                'tax' => [
                    'percentage' => $plan->tax_percentage,
                    'inclusive' => $plan->tax_inclusive,
                ],
                'is_featured' => $plan->is_featured,
                'is_popular' => $plan->is_popular,
                'metadata' => $plan->metadata ?? [],
            ],
        ]);
    }

    /**
     * Calcular precio con cupón de descuento
     */
    public function calculatePrice(Request $request, Plan $plan): JsonResponse
    {
        $request->validate([
            'currency' => 'sometimes|string|in:ARS,USD',
            'period' => 'sometimes|string|in:monthly,quarterly,yearly',
            'coupon_code' => 'sometimes|string',
        ]);

        if (!$plan->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Plan no disponible',
            ], 404);
        }

        $currency = $request->get('currency', 'ARS');
        $period = $request->get('period', $plan->billing_period);
        $couponCode = $request->get('coupon_code');

        $basePrice = $plan->getPriceWithDiscount($period, $currency);
        $finalPrice = $basePrice;
        $discount = null;

        // Aplicar cupón si se proporciona
        if ($couponCode) {
            $coupon = \App\Models\Coupon::where('code', $couponCode)
                ->where('is_active', true)
                ->first();

            if ($coupon && $coupon->isValid()) {
                $finalPrice = $plan->getDiscountedPrice($coupon, $currency);
                $discount = [
                    'code' => $coupon->code,
                    'type' => $coupon->type,
                    'description' => $coupon->getDiscountDescription(),
                    'original_price' => $basePrice,
                    'discount_amount' => $basePrice - $finalPrice,
                ];
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Cupón inválido o expirado',
                ], 400);
            }
        }

        // Calcular impuestos si aplica
        $taxes = 0;
        if ($plan->tax_percentage > 0 && !$plan->tax_inclusive) {
            $taxes = $finalPrice * ($plan->tax_percentage / 100);
        }

        $totalPrice = $finalPrice + $taxes;

        return response()->json([
            'success' => true,
            'data' => [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'currency' => $currency,
                'period' => $period,
                'base_price' => $basePrice,
                'final_price' => $finalPrice,
                'taxes' => $taxes,
                'total_price' => $totalPrice,
                'formatted_total_price' => ($currency === 'USD' ? 'USD $' : '$') . number_format($totalPrice, 2),
                'discount' => $discount,
                'tax_info' => [
                    'percentage' => $plan->tax_percentage,
                    'inclusive' => $plan->tax_inclusive,
                    'amount' => $taxes,
                ],
            ],
        ]);
    }

    /**
     * Verificar disponibilidad de cupón
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'coupon_code' => 'required|string',
        ]);

        $coupon = \App\Models\Coupon::where('code', $request->coupon_code)
            ->where('is_active', true)
            ->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Cupón no encontrado',
            ], 404);
        }

        if (!$coupon->isValid()) {
            $reason = 'Cupón no válido';
            if ($coupon->expires_at && $coupon->expires_at->isPast()) {
                $reason = 'Cupón expirado';
            } elseif ($coupon->max_redemptions && $coupon->redeemed_count >= $coupon->max_redemptions) {
                $reason = 'Cupón agotado';
            }

            return response()->json([
                'success' => false,
                'message' => $reason,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $coupon->code,
                'type' => $coupon->type,
                'description' => $coupon->getDiscountDescription(),
                'valid' => true,
                'expires_at' => $coupon->expires_at?->toDateString(),
                'remaining_uses' => $coupon->max_redemptions ? ($coupon->max_redemptions - $coupon->redeemed_count) : null,
            ],
        ]);
    }
}