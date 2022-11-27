<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Laravel\Scout\EngineManager;

class ServerController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'available'
        ]);
    }

    public function version(EngineManager $engine)
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

    public function openApi()
    {
        return response(
            file_get_contents(base_path('docs/open-api-spec.yml')),
            200,
            ['Content-Type' => 'application/x-yaml']
        );
    }
}
