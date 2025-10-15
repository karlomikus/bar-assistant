<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers\Public;

use Kami\Cocktail\Models\Bar;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Http\Controllers\Controller;
use Kami\Cocktail\Http\Resources\Public\BarResource;

class BarController extends Controller
{
    #[OAT\Get(path: '/public/{slugOrId}', tags: ['Public'], operationId: 'showPublicBar', description: 'Show public information about a single bar. To access this endpoint the bar must be marked as public.', summary: 'Show bar', parameters: [
        new OAT\Parameter(name: 'slugOrId', in: 'path', required: true, description: 'Database id of bar', schema: new OAT\Schema(type: 'string')),
        new BAO\Parameters\PageParameter(),
    ], security: [])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(BarResource::class),
    ])]
    #[BAO\NotFoundResponse]
    public function show(string $slugOrId): BarResource
    {
        $bar = Bar::where('slug', $slugOrId)->orWhere('id', $slugOrId)->firstOrFail();
        if (!$bar->isPublic()) {
            abort(404);
        }

        return new BarResource($bar);
    }
}
