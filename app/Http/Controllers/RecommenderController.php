<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\CocktailBasicResource;
use Kami\Cocktail\Services\CocktailRecommendationService;

class RecommenderController extends Controller
{
    #[OAT\Get(path: '/recommender/cocktails', tags: ['Recommender'], operationId: 'recommendCocktails', description: 'Recommends cocktails based on bar member favorites.', summary: 'Recommend cocktails', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(BAO\Schemas\CocktailBasic::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function cocktails(CocktailRecommendationService $cocktailRecommendationService, Request $request): JsonResource
    {
        $barMembership = $request->user()->getBarMembership(bar()->id);

        if ($barMembership === null) {
            abort(404);
        }

        $cocktails = $cocktailRecommendationService->recommend($barMembership, 5);

        return CocktailBasicResource::collection($cocktails);
    }
}
