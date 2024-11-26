<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Kami\Cocktail\Models\Bar;
use OpenApi\Attributes as OAT;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\BarIngredient;
use Kami\Cocktail\Models\UserIngredient;
use Kami\Cocktail\Models\CocktailFavorite;
use Kami\Cocktail\Repository\CocktailRepository;
use Kami\Cocktail\Models\Collection as CocktailCollection;

class StatsController extends Controller
{
    #[OAT\Get(path: '/bars/{id}/stats', tags: ['Bars'], operationId: 'showBarStats', description: 'Show detailed stats about a single bar', summary: 'Show bar stats', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(BAO\Schemas\BarStats::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function index(CocktailRepository $cocktailRepo, Request $request, int $id): JsonResponse
    {
        $bar = Bar::findOrFail($id);
        $barMembership = $request->user()->getBarMembership($bar->id)->load('userIngredients');
        $limit = 5;
        $stats = [];

        $popularIngredients = DB::table('cocktail_ingredients')
            ->select('ingredient_id as id', 'ingredients.name as name', 'ingredients.slug as slug', DB::raw('COUNT(ingredient_id) AS cocktails_count'))
            ->join('cocktails', 'cocktails.id', '=', 'cocktail_ingredients.cocktail_id')
            ->join('ingredients', 'ingredients.id', '=', 'cocktail_ingredients.ingredient_id')
            ->where('cocktails.bar_id', $bar->id)
            ->groupBy('ingredient_id')
            ->orderBy('cocktails_count', 'desc')
            ->limit($limit)
            ->get();

        $topRatedCocktails = DB::table('ratings')
            ->select('rateable_id AS id', 'cocktails.name as name', 'cocktails.slug as slug', DB::raw('AVG(rating) AS avg_rating'), DB::raw('COUNT(*) AS votes'))
            ->join('cocktails', 'cocktails.id', '=', 'ratings.rateable_id')
            ->where('rateable_type', Cocktail::class)
            ->where('cocktails.bar_id', $bar->id)
            ->groupBy('rateable_id')
            ->orderBy('avg_rating', 'desc')
            ->orderBy('votes', 'desc')
            ->limit($limit)
            ->get();

        $userFavoriteIngredients = DB::table('cocktail_ingredients')
            ->selectRaw('ingredients.id, ingredients.slug, ingredients.name, COUNT(cocktail_id) AS cocktails_count')
            ->whereIn('cocktail_id', function ($query) use ($barMembership) {
                $query->from('cocktail_favorites')->select('cocktail_id')->where('bar_membership_id', $barMembership->id);
            })
            ->join('ingredients', 'ingredients.id', '=', 'cocktail_ingredients.ingredient_id')
            ->groupBy('ingredient_id')
            ->orderBy('cocktails_count', 'DESC')
            ->limit($limit)
            ->get();

        $favoriteTags = DB::table('tags')
            ->selectRaw('tags.id, tags.name, COUNT(cocktail_favorites.cocktail_id) AS cocktails_count')
            ->join('cocktail_tag', 'cocktail_tag.tag_id', '=', 'tags.id')
            ->join('cocktail_favorites', 'cocktail_favorites.cocktail_id', '=', 'cocktail_tag.cocktail_id')
            ->where('cocktail_favorites.bar_membership_id', $barMembership->id)
            ->groupBy('tags.id')
            ->orderBy('cocktails_count', 'DESC')
            ->limit($limit)
            ->get();

        $stats['total_cocktails'] = Cocktail::where('bar_id', $bar->id)->count();
        $stats['total_ingredients'] = Ingredient::where('bar_id', $bar->id)->count();
        $stats['total_favorited_cocktails'] = CocktailFavorite::where('bar_membership_id', $barMembership->id)->count();
        $stats['total_shelf_cocktails'] = $cocktailRepo->getCocktailsByIngredients(
            $barMembership->userIngredients->pluck('ingredient_id')->toArray(),
            null,
            $barMembership->use_parent_as_substitute,
        )->count();
        $stats['total_bar_shelf_cocktails'] = $cocktailRepo->getCocktailsByIngredients(
            $bar->shelfIngredients->pluck('ingredient_id')->toArray(),
            null,
            $barMembership->use_parent_as_substitute,
        )->count();
        $stats['total_shelf_ingredients'] = UserIngredient::where('bar_membership_id', $barMembership->id)->count();
        $stats['total_bar_shelf_ingredients'] = BarIngredient::where('bar_id', $bar->id)->count();
        $stats['most_popular_ingredients'] = $popularIngredients;
        $stats['top_rated_cocktails'] = $topRatedCocktails;
        $stats['total_collections'] = CocktailCollection::where('bar_membership_id', $barMembership->id)->count();
        $stats['your_top_ingredients'] = $userFavoriteIngredients;
        $stats['total_bar_members'] = $bar->memberships()->count();
        $stats['favorite_tags'] = $favoriteTags;

        return response()->json(['data' => $stats]);
    }
}
