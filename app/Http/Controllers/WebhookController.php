<?php

namespace App\Http\Controllers;

use App\Models\WebhookLog;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function mercadopago(Request $request)
    {
        $payload = $request->all();
        $log = WebhookLog::create([
            'provider' => 'mp',
            'event_type' => $payload['type'] ?? null,
            'external_id' => $payload['data']['id'] ?? null,
            'payload' => $payload,
            'status' => 'received',
        ]);
        // TODO: procesar evento y actualizar suscripción/pago
        return response()->json(['success' => true, 'id' => $log->id]);
    }

    public function paypal(Request $request)
    {
        $payload = $request->all();
        $log = WebhookLog::create([
            'provider' => 'paypal',
            'event_type' => $payload['event_type'] ?? null,
            'external_id' => $payload['id'] ?? null,
            'payload' => $payload,
            'status' => 'received',
        ]);
        // TODO: procesar evento y actualizar suscripción/pago
        return response()->json(['success' => true, 'id' => $log->id]);
    }
}
