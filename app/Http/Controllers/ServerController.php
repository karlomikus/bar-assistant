<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Response;
use Laravel\Scout\EngineManager;
use Illuminate\Http\JsonResponse;

class ServerController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'available'
        ]);
    }

    public function version(EngineManager $engine): JsonResponse
    {
        /** @var \MeiliSearch\Client */
        $meilisearch = $engine->engine();

        return response()->json([
            'data' => [
                'name' => config('app.name'),
                'version' => config('bar-assistant.version'),
                'meilisearch_host' => config('scout.meilisearch.host'),
                'meilisearch_version' => $meilisearch->version()['pkgVersion'],
            ]
        ]);
    }

    public function openApi(): Response
    {
        return response(
            file_get_contents(base_path('docs/open-api-spec.yml')),
            200,
            ['Content-Type' => 'application/x-yaml']
        );
    }
}
