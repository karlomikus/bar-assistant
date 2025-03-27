<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Laravel\Scout\EngineManager;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\App;
use Kami\Cocktail\Services\VersionCheckService;

class ServerController extends Controller
{
    #[OAT\Get(path: '/server/version', tags: ['Server'], operationId: 'showServerVersion', description: 'Show server status and information', summary: 'Show information', security: [[]])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(BAO\Schemas\ServerVersion::class),
    ])]
    public function version(VersionCheckService $versionCheckService): JsonResponse
    {
        $searchHost = null;
        $searchVersion = null;
        if (config('scout.driver') === 'meilisearch') {
            /** @var \Meilisearch\Client */
            $meilisearch = resolve(EngineManager::class)->engine();

            $searchHost = config('scout.meilisearch.host');
            $searchVersion = $meilisearch->version()['pkgVersion'];
        }

        $githubReleaseVersion = $versionCheckService->getLatestVersion();

        return response()->json([
            'data' => [
                'version' => config('bar-assistant.version'),
                'latest_version' => $githubReleaseVersion,
                'is_latest' => $versionCheckService->isLatest($githubReleaseVersion, config('bar-assistant.version')),
                'type' => config('app.env'),
                'search_host' => $searchHost,
                'search_version' => $searchVersion,
                'is_feeds_enabled' => (bool) config('bar-assistant.enable_feeds') === true,
                'is_password_login_enabled' => config('bar-assistant.enable_password_login') === true,
            ]
        ]);
    }

    public function openApi(): Response
    {
        $spec = file_get_contents(base_path('docs/openapi-generated.yaml'));
        if ($spec === false) {
            abort(404);
        }

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
