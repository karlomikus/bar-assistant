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
    #[OAT\Get(path: '/explore/cocktails/{ulid}', tags: ['Explore'], summary: 'Show a public cocktail', parameters: [
        new OAT\Parameter(name: 'ulid', in: 'path', required: true, description: 'Public cocktail ULID', schema: new OAT\Schema(type: 'string')),
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
