<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Version
    |--------------------------------------------------------------------------
    |
    | Bar Assistant version.
    |
    */

    'version' => env('BAR_ASSISTANT_VERSION', 'develop'),

    /*
    |--------------------------------------------------------------------------
    | Allow registration
    |--------------------------------------------------------------------------
    |
    | This option determines if you allow registration endpoint. You can
    | still add new user via artisan commands.
    |
    */

    'allow_registration' => env(
        'ALLOW_REGISTRATION',
        true
    ),

    /*
    |--------------------------------------------------------------------------
    | [Experimental] Use parent ingredient as substitutes GH#95
    |--------------------------------------------------------------------------
    |
    | This option will modify how ingredients for cocktails you can make are
    | shown. Enabling this option will use ingredients parent ingredient
    | as a possible substitute.
    |
    */

    'parent_ingredient_as_substitute' => env(
        'PARENT_INGREDIENT_SUBSTITUTE',
        false
    ),

    /*
    |--------------------------------------------------------------------------
    | Max bars per user
    |--------------------------------------------------------------------------
    |
    | This will limit how many bars can a single user create
    |
    */

    'max_default_bars' => 1,
    'max_premium_bars' => 10,

    /*
    |--------------------------------------------------------------------------
    | Mail confirmation
    |--------------------------------------------------------------------------
    |
    | Require user to confirm email before accesing the application
    |
    */

    'mail_require_confirmation' => env('MAIL_REQUIRE_CONFIRMATION', false),

    'mail_reset_url' => env('MAIL_RESET_URL', null),
    'mail_confirm_url' => env('MAIL_CONFIRM_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Billing information
    |--------------------------------------------------------------------------
    |
    | Cashier configuration
    |
    */

    'enable_billing' => env('ENABLE_BILLING', false),
    'prices' => explode('|', env('BILLING_PRODUCT_PRICES', '')),
];
