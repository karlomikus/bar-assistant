<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\UserIngredient;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Models\Collection as CocktailCollection;

class StatsController extends Controller
{
    public function index(CocktailService $cocktailService, Request $request): JsonResponse
    {
        $limit = $request->get('limit', 5);
        $stats = [];

        $popularIngredientIds = DB::table('cocktail_ingredients')
            ->select('ingredient_id', DB::raw('COUNT(ingredient_id) AS cocktails_count'))
            ->groupBy('ingredient_id')
            ->orderBy('cocktails_count', 'desc')
            ->limit($limit)
            ->get();

        $topRatedCocktailIds = DB::table('ratings')
            ->select('rateable_id AS cocktail_id', DB::raw('AVG(rating) AS avg_rating'), DB::raw('COUNT(*) AS votes'))
            ->where('rateable_type', Cocktail::class)
            ->groupBy('rateable_id')
            ->orderBy('avg_rating', 'desc')
            ->orderBy('votes', 'desc')
            ->limit($limit)
            ->get();

        $userFavoriteIngredients = DB::table('cocktail_ingredients')
            ->selectRaw('ingredient_id, ingredients.name, COUNT(cocktail_id) AS cocktails_count')
            ->whereIn('cocktail_id', function ($query) use ($request) {
                $query->from('cocktail_favorites')->select('cocktail_id')->where('user_id', $request->user()->id);
            })
            ->join('ingredients', 'ingredients.id', '=', 'cocktail_ingredients.ingredient_id')
            ->groupBy('ingredient_id')
            ->orderBy('cocktails_count', 'DESC')
            ->limit($limit)
            ->get();

        $stats['total_cocktails'] = Cocktail::count();
        $stats['total_ingredients'] = Ingredient::count();
        $stats['total_shelf_cocktails'] = $cocktailService->getCocktailsByIngredients(
            $request->user()->shelfIngredients->pluck('ingredient_id')->toArray()
        )->count();
        $stats['total_shelf_ingredients'] = UserIngredient::where('user_id', $request->user()->id)->count();
        $stats['most_popular_ingredients'] = $popularIngredientIds;
        $stats['top_rated_cocktails'] = $topRatedCocktailIds;
        $stats['total_collections'] = CocktailCollection::where('user_id', $request->user()->id)->count();
        $stats['your_top_ingredients'] = $userFavoriteIngredients;

        return response()->json(['data' => $stats]);
    }
}
