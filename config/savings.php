<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JSON Storage Path
    |--------------------------------------------------------------------------
    |
    | Directory where the application keeps its JSON data files (one file per
    | collection, e.g. goals.json and transactions.json).
    |
    */

    'storage_path' => env('SAVINGS_STORAGE_PATH', storage_path('app/data')),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | ISO 4217 currency code used by the frontend when formatting amounts.
    |
    */

    'currency' => env('SAVINGS_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    'dashboard' => [
        'recent_activity_limit' => 6,
        'chart_months' => 6,
    ],

];
