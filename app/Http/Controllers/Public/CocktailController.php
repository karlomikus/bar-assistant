<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers\Public;

use Illuminate\Http\Request;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Cache;
use Kami\Cocktail\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Kami\Cocktail\Http\Filters\PublicCocktailQueryFilter;
use Kami\Cocktail\Http\Resources\ExploreCocktailResource;
use Kami\Cocktail\Http\Resources\Public\CocktailResource;

class CocktailController extends Controller
{
    public function index(Request $request, int $barId): JsonResource
    {
        $bar = Bar::findOrFail($barId);

        if (!$bar->isPublic()) {
            abort(404);
        }

        try {
            $cocktailsQuery = new PublicCocktailQueryFilter($bar);
        } catch (InvalidFilterQuery $e) {
            abort(400, $e->getMessage());
        }

        $queryParams = $request->only([
            'id',
            'name',
            'ingredient_name',
            'ingredient_substitute_id',
            'ingredient_id',
            'tag_id',
            'created_user_id',
            'glass_id',
            'cocktail_method_id',
            'bar_shelf',
            'abv_min',
            'abv_max',
            'main_ingredient_id',
            'total_ingredients',
            'parent_cocktail_id',
            'per_page',
            'sort',
            'page',
        ]);
        ksort($queryParams);
        $queryString = http_build_query($queryParams);
        $cacheKey = 'public_cocktails_' . $barId . '_' . sha1($queryString);

        $cocktails = Cache::remember($cacheKey, 3600, function () use ($cocktailsQuery, $request) {
            return $cocktailsQuery->paginate($request->get('per_page', 25));
        });

        return CocktailResource::collection($cocktails->withQueryString());
    }

    public function show(string $barSlug, string $id): ExploreCocktailResource
    {
        $cocktail = Cocktail::where('public_id', $id)->firstOrFail()->load('ingredients.ingredient');

        return new ExploreCocktailResource($cocktail);
    }
}
