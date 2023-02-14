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

    'version' => 'v1.4.0',

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

];
