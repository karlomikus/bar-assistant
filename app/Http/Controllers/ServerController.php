<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Laravel\Scout\EngineManager;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\App;

class ServerController extends Controller
{
    #[OAT\Get(path: '/server/version', tags: ['Server'], summary: 'Show server information', security: [[]])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\ServerVersion::class),
    ])]
    public function version(): JsonResponse
    {
        $searchHost = null;
        $searchVersion = null;
        if (config('scout.driver') === 'meilisearch') {
            /** @var \Meilisearch\Client */
            $meilisearch = resolve(EngineManager::class)->engine();

            $searchHost = config('scout.meilisearch.host');
            $searchVersion = $meilisearch->version()['pkgVersion'];
        }

        return response()->json([
            'data' => [
                'version' => config('bar-assistant.version'),
                'type' => config('app.env'),
                'search_host' => $searchHost,
                'search_version' => $searchVersion,
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
