<h1>Instrucciones de Transferencia</h1>
<p>Hola {{ $payment->user->name }},</p>
<p>Para activar tu membresía del plan {{ $subscription->plan->name }}, realiza una transferencia por <strong>{{ number_format($payment->amount_cents/100, 2) }} {{ $payment->currency }}</strong> a:</p>
<ul>
  <li>Cuenta: {{ env('BILLING_BANK_ACCOUNT', 'CBU/ALIAS AQUÍ') }}</li>
  <li>Titular: {{ env('BILLING_BANK_HOLDER', 'Nombre del titular') }}</li>
</ul>
<p>Luego, envía el comprobante a <a href="mailto:{{ env('SUPPORT_EMAIL', config('mail.from.address')) }}">{{ env('SUPPORT_EMAIL', config('mail.from.address')) }}</a>
@if(env('SUPPORT_WHATSAPP')) o por WhatsApp a <a href="https://wa.me/{{ env('SUPPORT_WHATSAPP') }}">{{ env('SUPPORT_WHATSAPP') }}</a>@endif.
</p>
<p>Referencia de pago: #{{ $payment->id }}</p>
<p>¡Gracias!</p>
