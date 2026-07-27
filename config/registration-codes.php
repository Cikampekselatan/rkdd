<?php

return [
    'prefix' => env('REGISTRATION_CODE_PREFIX', 'SKUAD'),
    'length' => (int) env('REGISTRATION_CODE_LENGTH', 20),
    'alphabet' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',
    'hash_key' => env('REGISTRATION_CODE_HASH_KEY', env('APP_KEY')),
];
