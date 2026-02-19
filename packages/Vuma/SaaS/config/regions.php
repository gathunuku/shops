<?php

return [
    // Country (ISO 3166-1 alpha-2) => currency + payment channels in priority order
    'KE' => ['currency' => 'KES', 'channels' => ['mpesa_ke', 'paystack', 'card']],
    'TZ' => ['currency' => 'TZS', 'channels' => ['vodacom_tz', 'airtel_money', 'paystack']],
    'UG' => ['currency' => 'UGX', 'channels' => ['mtn_momo', 'airtel_money', 'paystack']],
    'RW' => ['currency' => 'RWF', 'channels' => ['mtn_momo', 'paystack']],
    'ZM' => ['currency' => 'ZMW', 'channels' => ['mtn_momo', 'airtel_money', 'paystack']],
    'MW' => ['currency' => 'MWK', 'channels' => ['airtel_money', 'paystack']],
    'GH' => ['currency' => 'GHS', 'channels' => ['mtn_momo', 'paystack', 'card']],
    'NG' => ['currency' => 'NGN', 'channels' => ['card', 'paystack', 'bank_transfer', 'ussd']],
    'ZA' => ['currency' => 'ZAR', 'channels' => ['eft', 'card', 'paystack']],
    'CI' => ['currency' => 'XOF', 'channels' => ['mtn_momo', 'airtel_money', 'paystack']],
    'SN' => ['currency' => 'XOF', 'channels' => ['airtel_money', 'paystack']],
    'CM' => ['currency' => 'XAF', 'channels' => ['mtn_momo', 'airtel_money', 'paystack']],
];
