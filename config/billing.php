<?php

return [
    // Días de gracia post vencimiento para evitar bloquear por demoras de conciliación
    'grace_days' => env('BILLING_GRACE_DAYS', 0),

    // Catálogo simple de planes. Solo informativo.
    'plans' => [
        'free' => null,
        'monthly' => 'P1M', // ISO8601 period notation (informativo)
        'annual' => 'P1Y',
    ],
];
