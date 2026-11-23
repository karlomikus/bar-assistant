<?php

namespace Kami\Cocktail\Http\Middleware;

class VerifyCsrfToken extends \Illuminate\Foundation\Http\Middleware\PreventRequestForgery
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}
