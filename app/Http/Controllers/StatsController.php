<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Kami\Cocktail\Models\Bar;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\BarIngredient;
use Kami\Cocktail\Models\CocktailFavorite;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Http\Resources\BarTopStatsResource;
use Kami\Cocktail\Http\Resources\BarTotalStatsResource;
use Kami\Cocktail\Http\Resources\UserTasteProfileResource;
use Kami\Cocktail\Models\Collection as CocktailCollection;
use BarAssistant\Application\Recommendation\RecommendationService;
use Kami\Cocktail\Http\Resources\BarIngredientDistributionResource;
use BarAssistant\Application\Recommendation\DTO\GetUserTasteProfileRequest;

class StatsController extends Controller
{
    #[OAT\Get(path: '/bars/{id}/stats/taste', tags: ['Stats'], operationId: 'getMemberTaste', description: 'Returns taste profile derived from bar member favorites and ratings.', summary: 'Taste profile', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(UserTasteProfileResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function taste(RecommendationService $cocktailRecommendationService, Request $request, int $id): UserTasteProfileResource
    {
        $barMembership = $request->user()->getBarMembership($id);

        if ($barMembership === null) {
            abort(404);
        }

        $profile = $cocktailRecommendationService->getUserTasteProfile(new GetUserTasteProfileRequest(
            memberId: $barMembership->id,
        ));

        return new UserTasteProfileResource($profile);
    }

    #[OAT\Get(path: '/bars/{id}/stats/totals', tags: ['Stats'], operationId: 'getBarTotals', description: 'Get total stats for a bar', summary: 'Bar totals', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(BarTotalStatsResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function totals(CocktailService $cocktailRepo, Request $request, int $id): BarTotalStatsResource
    {
        $bar = Bar::findOrFail($id);
        $barMembership = $request->user()->getBarMembership($bar->id);

        if ($barMembership === null) {
            abort(403);
        }

        $stats['total_cocktails'] = Cocktail::where('bar_id', $bar->id)->count();
        $stats['total_ingredients'] = Ingredient::where('bar_id', $bar->id)->count();
        $stats['total_favorited_cocktails'] = CocktailFavorite::where('bar_membership_id', $barMembership->id)->count();
        $stats['total_shelf_cocktails'] = $cocktailRepo->getCocktailsByIngredients(
            $barMembership->userIngredients->pluck('ingredient_id')->toArray(),
            $bar->id,
            null,
        )->count();
        $stats['total_bar_shelf_cocktails'] = $cocktailRepo->getCocktailsByIngredients(
            $bar->shelfIngredients->pluck('ingredient_id')->toArray(),
            $bar->id,
            null,
        )->count();
        $stats['total_bar_shelf_ingredients'] = BarIngredient::where('bar_id', $bar->id)->count();
        $stats['total_collections'] = CocktailCollection::where('bar_membership_id', $barMembership->id)->count();
        $stats['total_bar_members'] = $bar->memberships()->count();

        return new BarTotalStatsResource($stats);
    }

    #[OAT\Get(path: '/bars/{id}/stats/ingredient-distribution', tags: ['Stats'], operationId: 'getBarIngredientDistribution', description: 'Get ingredient distribution for a bar', summary: 'Ingredient distribution', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(BarIngredientDistributionResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function ingredientDistribution(Request $request, int $id): BarIngredientDistributionResource
    {
        $bar = Bar::findOrFail($id);
        $barMembership = $request->user()->getBarMembership($bar->id);

        if ($barMembership === null) {
            abort(403);
        }

        $mainCategoryIngredientDistribution = Ingredient::query()
            ->onlyRootIngredients($bar->id)
            ->select('id', 'slug', 'name')
            ->get()
            ->map(function (Ingredient $ingredient) use ($bar) {
                $ingredientsCount = BarIngredient::query()
                    ->whereIn('ingredient_id', function ($query) use ($ingredient, $bar) {
                        $query->from('ingredients')
                            ->select('id')
                            ->where('bar_id', $bar->id)
                            ->where(function ($q) use ($ingredient) {
                                $q->where('parent_ingredient_id', $ingredient->id)
                                    ->orWhere('materialized_path', 'LIKE', $ingredient->id . '/%');
                            });
                    })
                    ->where('bar_id', $bar->id)
                    ->count();

                return [
                    'id' => $ingredient->id,
                    'slug' => $ingredient->slug,
                    'name' => $ingredient->name,
                    'ingredients_count' => $ingredientsCount,
                ];
            })
            ->values();

        return new BarIngredientDistributionResource([
            'main_category_ingredient_distribution' => $mainCategoryIngredientDistribution,
        ]);
    }

    #[OAT\Get(path: '/bars/{id}/stats/top', tags: ['Stats'], operationId: 'getBarTopRated', description: 'Get top rated resources for a bar', summary: 'Top stats', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(BarTopStatsResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function top(CocktailService $cocktailRepo, Request $request, int $id): BarTopStatsResource
    {
        $bar = Bar::findOrFail($id);
        $barMembership = $request->user()->getBarMembership($bar->id);

        if ($barMembership === null) {
            abort(403);
        }

        $barMembership->load('userIngredients');

        $limit = 8;
        $stats = [];

        $topRatedCocktails = $cocktailRepo->getTopRatedCocktails($bar->id, $limit);

        $topMemberIngredients = DB::table('cocktail_ingredients')
            ->selectRaw('ingredients.id, ingredients.slug, ingredients.name, COUNT(cocktail_id) AS cocktails_count')
            ->whereIn('cocktail_id', function ($query) use ($barMembership) {
                $query->from('cocktail_favorites')->select('cocktail_id')->where('bar_membership_id', $barMembership->id);
            })
            ->join('ingredients', 'ingredients.id', '=', 'cocktail_ingredients.ingredient_id')
            ->groupBy('ingredient_id')
            ->orderBy('cocktails_count', 'DESC')
            ->limit($limit)
            ->get();

        $stats['top_bar_cocktails'] = $topRatedCocktails;
        $stats['top_member_ingredients'] = $topMemberIngredients;

        return new BarTopStatsResource($stats);
    }
}
