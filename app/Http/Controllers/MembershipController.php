<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Coupon;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Ticket;
use Illuminate\Support\Facades\Mail;
use App\Mail\BankTransferInstructions;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    public function plans()
    {
        return response()->json([
            'success' => true,
            'data' => Plan::where('is_active', true)->orderBy('price_cents')->get(),
        ]);
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'plan_code' => 'required|string|exists:plans,code',
            'provider' => 'required|in:mp,paypal,offline',
            'coupon' => 'nullable|string|exists:coupons,code',
        ]);

        $user = $request->user();
        $plan = Plan::where('code', $data['plan_code'])->firstOrFail();
        $coupon = isset($data['coupon']) ? Coupon::where('code', $data['coupon'])->where('is_active', true)->first() : null;

        // Crear/actualizar Subscription en estado inicial
        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'provider' => $data['provider'],
            'status' => $data['provider'] === 'offline' ? 'on_hold' : 'in_trial',
            'auto_renew' => $data['provider'] !== 'offline',
            'trial_ends_at' => $coupon && $coupon->type === 'free_time' && $coupon->free_days ? now()->addDays($coupon->free_days) : ( $plan->trial_days ? now()->addDays($plan->trial_days) : null ),
            'coupon_id' => $coupon->id ?? null,
        ]);

        if ($data['provider'] === 'offline') {
            // Crear Payment pending y devolver instrucciones (UI deberá mostrar email/whatsapp)
            $payment = Payment::create([
                'subscription_id' => $sub->id,
                'user_id' => $user->id,
                'provider' => 'offline',
                'amount_cents' => $plan->price_cents,
                'currency' => $plan->currency,
                'status' => 'pending',
            ]);

            // Crear ticket de soporte para seguimiento
            Ticket::create([
                'user_id' => $user->id,
                'subject' => 'Pago por transferencia pendiente',
                'message' => 'El usuario inició un pago offline. Referencia Payment #'.$payment->id,
                'status' => 'open',
                'priority' => 'normal',
                'tags' => ['billing','offline'],
            ]);

            // Enviar email con instrucciones
            try {
                Mail::to($user->email)->queue(new BankTransferInstructions($payment, $sub));
            } catch (\Throwable $e) {
                // no bloquear el flujo si el mail falla
            }

            return response()->json([
                'success' => true,
                'mode' => 'offline',
                'subscription_id' => $sub->id,
                'payment_id' => $payment->id,
                'instructions' => [
                    'bank_account' => env('BILLING_BANK_ACCOUNT', 'CBU/ALIAS AQUÍ'),
                    'contact_email' => env('SUPPORT_EMAIL', config('mail.from.address')),
                    'contact_whatsapp' => env('SUPPORT_WHATSAPP'),
                    'message' => 'Realiza la transferencia y envía el comprobante por email o WhatsApp para validar el pago.',
                ],
            ]);
        }

        // Stubs para redirección a MP/PayPal: en siguientes fases usaremos providers concretos
        return response()->json([
            'success' => true,
            'mode' => 'redirect',
            'provider' => $data['provider'],
            'subscription_id' => $sub->id,
            'redirect_url' => url('/payments/redirect/'. $data['provider'] .'/'. $sub->id),
        ]);
    }
}
