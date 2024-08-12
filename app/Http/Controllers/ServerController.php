<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;

class ServerController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'available'
        ]);
    }

    public function version(): JsonResponse
    {
        return response()->json([
            'data' => [
                'version' => config('bar-assistant.version'),
                'type' => config('app.env'),
                'search_host' => null,//$search->getHost(),
                'search_version' => null,//$search->getVersion(),
            ]
        ]);
    }

    public function openApi(): Response
    {
        $spec = file_get_contents(base_path('docs/openapi-generated.yaml'));
        if (!App::environment('production')) {
            $spec = str_replace('{{VERSION}}', 'develop', $spec);
        }

        if (request()->getHost() === 'api.barassistant.app') {
            $spec = str_replace('{{VERSION}}', 'cloud', $spec);
        }

        return response(
            $spec,
            200,
            ['Content-Type' => 'application/x-yaml']
        );
    }
}
