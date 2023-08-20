<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Models\UserIngredient;
use Kami\Cocktail\Models\UserShoppingList;
use Kami\Cocktail\Services\CocktailService;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\UserIngredientResource;
use Kami\Cocktail\Http\Requests\UserIngredientBatchRequest;

class ShelfController extends Controller
{
    public function ingredients(Request $request): JsonResource
    {
        $barMembership = $request->user()->getBarMembership(bar()->id);
        $userIngredients = $barMembership
            ->userIngredients
            ->sortBy('ingredient.name')
            ->load('ingredient');

        return UserIngredientResource::collection($userIngredients);
    }

    public function cocktails(CocktailService $cocktailService, Request $request): JsonResponse
    {
        $barMembership = $request->user()->getBarMembership(bar()->id);
        $limit = $request->has('limit') ? (int) $request->get('limit') : null;

        $cocktailIds = $cocktailService->getCocktailsByIngredients(
            $barMembership->userIngredients->pluck('ingredient_id')->toArray(),
            $limit
        );

        return response()->json([
            'data' => $cocktailIds
        ]);
    }

    public function batchStore(UserIngredientBatchRequest $request): JsonResource
    {
        $barMembership = $request->user()->getBarMembership(bar()->id);
        $ingredientIds = $request->post('ingredient_ids');

        // Let's remove ingredients from shopping list since they are on our shelf now
        UserShoppingList::whereIn('ingredient_id', $ingredientIds)->delete();

        $models = [];
        foreach ($ingredientIds as $ingId) {
            $userIngredient = new UserIngredient();
            $userIngredient->ingredient_id = $ingId;
            $models[] = $userIngredient;
        }

        $shelfIngredients = $barMembership->userIngredients()->saveMany($models);

        return UserIngredientResource::collection($shelfIngredients);
    }

    public function batchDelete(Request $request): Response
    {
        $barMembership = $request->user()->getBarMembership(bar()->id);
        $ingredientIds = $request->post('ingredient_ids');

        try {
            $barMembership->userIngredients()->whereIn('ingredient_id', $ingredientIds)->delete();
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        return response(null, 204);
    }
}
