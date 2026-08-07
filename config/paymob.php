<?php

return [

    'public_key' => env('PAYMOB_PUBLIC_KEY'),
    'secret_key' => env('PAYMOB_SECRET_KEY'),
    'hmac_secret' => env('PAYMOB_HMAC_SECRET'),

    'integrations' => [
        'card' => env('PAYMOB_INTEGRATION_CARD'),
        'wallet' => env('PAYMOB_INTEGRATION_WALLET'),
    ],

    'base_url' => env('PAYMOB_BASE_URL', 'https://accept.paymob.com'),
    'mode' => env('PAYMOB_MODE', 'sandbox'),

];
