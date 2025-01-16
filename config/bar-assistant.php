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
    'local_login_enabled' => env('LOCAL_LOGIN_ENABLED', true),

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

    /*
    |--------------------------------------------------------------------------
    | OAuth login
    |--------------------------------------------------------------------------
    |
    | OAuth login configuration
    |
    */
    'oauth_login_enabled' => env('OAUTH_LOGIN_ENABLED', false),
    'oauth_login_providers' => \Kami\Cocktail\Utils::parseJsonFile(
        base_path(env('OAUTH_LOGIN_PROVIDERS_FILE', 'resources/oauth/providers.json')),
        [],
        fn($data) => \Kami\Cocktail\OAuth\OAuthProvider::fromArray($data, env('OAUTH_LOGIN_REDIRECT_URI', ''))
    ),
    'oauth_login_redirect_uri' => env('OAUTH_LOGIN_REDIRECT_URI', ''),
];
