<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Kami\Cocktail\Models\Bar;
use OpenApi\Attributes as OAT;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Services\IngredientService;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\CocktailBasicResource;
use BarAssistant\Application\Recommendation\RecommendationService;
use BarAssistant\Application\Recommendation\DTO\GetRecommendationsRequest;

class RecommenderController extends Controller
{
    #[OAT\Get(path: '/recommender/cocktails', tags: ['Recommender'], operationId: 'recommendCocktails', description: 'Recommends cocktails based on bar member favorites.', summary: 'Recommend cocktails', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(CocktailBasicResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function cocktails(RecommendationService $cocktailRecommendationService, Request $request): JsonResource
    {
        $barMembership = $request->user()->getBarMembership(bar()->id);

        if ($barMembership === null) {
            abort(404);
        }

        $cocktails = $cocktailRecommendationService->getRecommendations(new GetRecommendationsRequest(
            memberId: $barMembership->id,
            limit: 8,
        ));

        $cocktails = Cocktail::whereIn('id', array_map(fn ($c) => $c->cocktailId, $cocktails))->with('images', 'ingredients.ingredient')->get();

        return CocktailBasicResource::collection($cocktails);
    }

    #[OAT\Get(path: '/bars/{id}/ingredients/recommend', tags: ['Bars: Shelf'], operationId: 'recommendBarIngredients', description: 'Shows a list of ingredients that will increase total bar shelf cocktails when added to bar shef', summary: 'Recommend bar ingredients', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(BAO\Schemas\IngredientRecommend::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function recommendBarIngredients(Request $request, IngredientService $ingredientRepo, int $id): JsonResponse
    {
        $bar = Bar::findOrFail($id);
        if ($request->user()->cannot('show', $bar)) {
            abort(403);
        }

        $possibleIngredients = $ingredientRepo->getIngredientsForPossibleCocktails($bar->id, $bar->shelfIngredients->pluck('ingredient_id')->toArray());

        return response()->json(['data' => $possibleIngredients]);
    }
}
