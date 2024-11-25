<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\ExploreCocktailResource;

class ExploreController extends Controller
{
    #[OAT\Get(path: '/explore/cocktails/{public_id}', tags: ['Explore'], operationId: 'showPublicCocktail', description: 'Show details from a cocktail using a public id', summary: 'Show cocktail', parameters: [
        new OAT\Parameter(name: 'public_id', in: 'path', required: true, description: 'Public cocktail id', schema: new OAT\Schema(type: 'string', format: 'ulid')),
    ], security: [])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\CocktailExplore::class),
    ])]
    #[BAO\NotFoundResponse]
    public function cocktail(string $publicId): JsonResource
    {
        $cocktail = Cocktail::where('public_id', $publicId)->firstOrFail()->load('ingredients.ingredient');

        return new ExploreCocktailResource($cocktail);
    }
}
