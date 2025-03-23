<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\FeedRecipeResource;
use Kami\Cocktail\Services\Feeds\RecipeFeedsService;

class FeedsController extends Controller
{
    #[OAT\Get(path: '/feeds', tags: ['Feeds'], operationId: 'listFeeds', description: 'Show a list of news and recipes from RSS/Atom feeds', summary: 'List feeds')]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(FeedRecipeResource::class),
    ])]
    public function feeds(RecipeFeedsService $service): JsonResource
    {
        $feedRecipes = $service->fetch();

        return FeedRecipeResource::collection($feedRecipes);
    }
}
