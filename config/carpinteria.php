<?php

return [
    // Plazo de entrega por defecto (días corridos) de cada trabajo nuevo; se puede cambiar por trabajo.
    'default_lead_time_days' => (int) env('PLAZO_DIAS', 30),

    // Valores por defecto de cada presupuesto nuevo.
    'default_margin_pct' => (float) env('PRESUPUESTO_MARGEN', 35),
    'default_tax_pct' => (float) env('PRESUPUESTO_IVA', 21),

    // Datos que se imprimen en el PDF del presupuesto.
    'business' => [
        'name' => env('APP_NAME', 'Carpintería'),
        'phone' => env('EMPRESA_TELEFONO'),
        'email' => env('EMPRESA_EMAIL'),
        'address' => env('EMPRESA_DIRECCION'),
        'tax_id' => env('EMPRESA_CUIT'),
    ],
];
