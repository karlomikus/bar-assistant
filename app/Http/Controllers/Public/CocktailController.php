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
    #[OAT\Get(path: '/public/{barId}/cocktails', tags: ['Public'], operationId: 'listPublicBarCocktails', description: 'List and filter bar cocktails. To access this endpoint the bar must be marked as public.', summary: 'List cocktails', parameters: [
        new OAT\Parameter(name: 'barId', in: 'path', required: true, description: 'Database id of bar', schema: new OAT\Schema(type: 'number')),
        new BAO\Parameters\PageParameter(),
        new OAT\Parameter(name: 'filter', in: 'query', description: 'Filter by attributes. You can specify multiple matching filter values by passing a comma separated list of values.', explode: true, style: 'deepObject', schema: new OAT\Schema(type: 'object', properties: [
            new OAT\Property(property: 'name', type: 'string', description: 'Filter by cocktail names(s) (fuzzy search)'),
            new OAT\Property(property: 'ingredient_name', type: 'string', description: 'Filter by cocktail ingredient names(s) (fuzzy search)'),
            new OAT\Property(property: 'bar_shelf', type: 'boolean', description: 'Show only cocktails on the bar shelf'),
            new OAT\Property(property: 'abv_min', type: 'number', description: 'Filter by greater than or equal ABV'),
            new OAT\Property(property: 'abv_max', type: 'number', description: 'Filter by less than or equal ABV'),
        ])),
        new OAT\Parameter(name: 'sort', in: 'query', description: 'Sort by attributes. Available attributes: `name`, `created_at`, `average_rating`, `user_rating`, `abv`, `total_ingredients`, `missing_ingredients`, `missing_bar_ingredients`, `favorited_at`, `random`.', schema: new OAT\Schema(type: 'string')),
    ], security: [])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\PaginateData(CocktailResource::class),
    ])]
    #[BAO\NotFoundResponse]
    public function index(Request $request, int $barId): JsonResource
    {
        $bar = Bar::findOrFail($barId);
        if (!$bar->is_public) {
            abort(404);
        }

        $queryParams = $request->only(['filter', 'sort', 'page']);
        ksort($queryParams);
        $cacheKey = 'public_cocktails_index:' . $barId . ':' . sha1(http_build_query($queryParams));

        if (Cache::has($cacheKey)) {
            $cocktails = Cache::get($cacheKey);

            return CocktailResource::collection($cocktails->withQueryString());
        }

        try {
            $cocktailsQuery = new PublicCocktailQueryFilter($bar);
        } catch (InvalidFilterQuery $e) {
            abort(400, $e->getMessage());
        }

        $cocktails = $cocktailsQuery->paginate(50);

        Cache::put($cacheKey, $cocktails, 3600);

        return CocktailResource::collection($cocktails->withQueryString());
    }

    #[OAT\Get(path: '/public/{barId}/cocktails/{slugOrPublicId}', tags: ['Public'], operationId: 'showPublicBarCocktail', description: 'Show public information about cocktail. If valid public ID is provided it will used, if not it will use cocktail slug.', summary: 'Show cocktail', parameters: [
        new OAT\Parameter(name: 'barId', in: 'path', required: true, description: 'Database id of bar', schema: new OAT\Schema(type: 'number')),
        new OAT\Parameter(name: 'slugOrPublicId', in: 'path', required: true, description: 'Cocktail slug or public id (ULID)', schema: new OAT\Schema(type: 'string')),
    ], security: [])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(CocktailResource::class),
    ])]
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
