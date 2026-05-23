<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Cocktail;
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
}
