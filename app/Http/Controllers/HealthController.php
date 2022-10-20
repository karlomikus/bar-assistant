<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

class HealthController extends Controller
{
    public function version()
    {
        return response()->json([
            'data' => [
                'name' => 'Bar Assistant',
                'version' => 'v0.1.0'
            ]
        ]);
    }
}
