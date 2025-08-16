<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers\Public;

use Illuminate\Http\Request;
use Kami\Cocktail\Models\Bar;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Cache;
use Kami\Cocktail\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Kami\Cocktail\Http\Filters\PublicCocktailQueryFilter;
use Kami\Cocktail\Http\Resources\Public\CocktailResource;

class CocktailController extends Controller
{
    public function index(Request $request, int $barId): JsonResource
    {
        $bar = Bar::findOrFail($barId);
        if (!$bar->is_public) {
            abort(404);
        }

        try {
            $cocktailsQuery = new PublicCocktailQueryFilter($bar);
        } catch (InvalidFilterQuery $e) {
            abort(400, $e->getMessage());
        }

        $queryParams = $request->only([
            'filter',
            'sort',
            'page',
        ]);
        ksort($queryParams);
        $queryString = http_build_query($queryParams);
        $cacheKey = 'public_cocktails_index_' . $barId . '_' . sha1($queryString);

        $cocktails = Cache::remember($cacheKey, 3600, function () use ($cocktailsQuery) {
            return $cocktailsQuery->paginate(50);
        });

        return CocktailResource::collection($cocktails->withQueryString());
    }

    #[OAT\Get(path: '/public/{barId}/cocktails/{slugOrPublicId}', tags: ['Public'], operationId: 'showPublicBarCocktail', description: 'Show public information about cocktail. If valid public ID is provided it will used, if not it will use cocktail slug.', summary: 'Show cocktail', parameters: [
        new OAT\Parameter(name: 'barId', in: 'path', required: true, description: 'Database id of bar', schema: new OAT\Schema(type: 'number')),
        new OAT\Parameter(name: 'slugOrPublicId', in: 'path', required: true, description: 'Cocktail slug or public id (ULID)', schema: new OAT\Schema(type: 'string')),
    ], security: [])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(CocktailResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function show(int $barId, string $slugOrPublicId): CocktailResource
    {
        $cocktail = Cocktail::where('bar_id', $barId)
            ->where('public_id', $slugOrPublicId)
            ->orWhere('slug', $slugOrPublicId)
            ->with('ingredients.ingredient', 'ingredients.substitutes.ingredient', 'images', 'tags', 'utensils')
            ->firstOrFail();

        if ($cocktail->public_id === $slugOrPublicId) {
            return new CocktailResource($cocktail);
        }

        $bar = Bar::findOrFail($barId);
        if (!$bar->is_public) {
            abort(404);
        }

        return new CocktailResource($cocktail);
    }
}
