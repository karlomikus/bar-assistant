<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Laravel\Scout\EngineManager;

class HealthController extends Controller
{
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
}
