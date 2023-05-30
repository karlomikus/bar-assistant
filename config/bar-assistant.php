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

    'version' => env('BAR_ASSISTANT_VERSION', 'v0-dev'),

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
    | Disable login
    |--------------------------------------------------------------------------
    |
    | This option will disable the need to authenticate with token to access the api
    |
    */

    'disable_login' => env(
        'DISABLE_LOGIN',
        false
    ),

];
