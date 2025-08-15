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
    #[OAT\Get(path: '/public/{barId}', tags: ['Public'], operationId: 'showPublicBar', description: 'Show public information about a single bar.', summary: 'Show bar', parameters: [
        new OAT\Parameter(name: 'barId', in: 'path', required: true, description: 'Database id of bar', schema: new OAT\Schema(type: 'number')),
    ], security: [])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(BarResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function show(int $barId): BarResource
    {
        $bar = Bar::findOrFail($barId);
        if (!$bar->is_public) {
            abort(404);
        }

        return new BarResource($bar);
    }
}
