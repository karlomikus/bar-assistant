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

    public function show(int $barId, string $slugOrPublicId): CocktailResource
    {
        $bar = Bar::findOrFail($barId);
        if (!$bar->is_public) {
            abort(404);
        }

        $cocktail = Cocktail::where('public_id', $slugOrPublicId)
            ->orWhere('slug', $slugOrPublicId)
            ->firstOrFail()
            ->load('ingredients.ingredient', 'ingredients.substitutes.ingredient', 'images', 'tags', 'utensils');

        return new CocktailResource($cocktail);
    }
}
