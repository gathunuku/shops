<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Base domain for tenant subdomains
    |--------------------------------------------------------------------------
    */
    'base_domain' => env('SAAS_BASE_DOMAIN', 'shops.vumacloud.com'),

    /*
    |--------------------------------------------------------------------------
    | Default plan for new tenants
    |--------------------------------------------------------------------------
    */
    'default_plan' => env('SAAS_DEFAULT_PLAN', 'starter'),

    /*
    |--------------------------------------------------------------------------
    | Trial days for new tenants (0 = no trial)
    |--------------------------------------------------------------------------
    */
    'trial_days' => (int) env('SAAS_TRIAL_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Billing
    |--------------------------------------------------------------------------
    */
    'billing' => [
        'provider'   => env('SAAS_BILLING_PROVIDER', 'paystack'), // paystack | momo_invoice
        'grace_days' => (int) env('SAAS_GRACE_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Grace period before suspension (in days)
    | Accessed directly as config('saas.grace_days') for convenience
    |--------------------------------------------------------------------------
    */
    'grace_days' => (int) env('SAAS_GRACE_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Dunning settings
    |--------------------------------------------------------------------------
    */
    'dunning' => [
        'max_attempts'       => (int) env('SAAS_DUNNING_MAX_ATTEMPTS', 5),
        'retry_interval_hrs' => (int) env('SAAS_DUNNING_RETRY_HRS', 24),
    ],
];
