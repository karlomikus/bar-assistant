<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Models\UserIngredient;
use Kami\Cocktail\Models\UserShoppingList;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\UserIngredientResource;
use Kami\Cocktail\Http\Requests\UserIngredientBatchRequest;

class ShelfController extends Controller
{
    public function index(Request $request): JsonResource
    {
        $userIngredients = $request->user()
            ->shelfIngredients
            ->sortBy('ingredient.name')
            ->load('ingredient');

        return UserIngredientResource::collection($userIngredients);
    }

    public function save(Request $request, int $ingredientId): JsonResponse
    {
        // Remove ingredient from the shopping list if it exists
        UserShoppingList::where('ingredient_id', $ingredientId)->delete();

        // Check if ingredient is already in the shelf, and add it if it's not
        if (!$request->user()->shelfIngredients->contains('ingredient_id', $ingredientId)) {
            $userIngredient = new UserIngredient();
            $userIngredient->ingredient_id = $ingredientId;
            $shelfIngredient = $request->user()->shelfIngredients()->save($userIngredient);
        } else {
            $shelfIngredient = $request->user()->shelfIngredients->where('ingredient_id', $ingredientId)->first();
        }

        return (new UserIngredientResource($shelfIngredient))->response()->setStatusCode(200);
    }

    public function batch(UserIngredientBatchRequest $request): JsonResource
    {
        $ingredientIds = $request->post('ingredient_ids');

        // Let's remove ingredients from shopping list since they are on our shelf now
        UserShoppingList::whereIn('ingredient_id', $ingredientIds)->delete();

        $models = [];
        foreach ($ingredientIds as $ingId) {
            $userIngredient = new UserIngredient();
            $userIngredient->ingredient_id = $ingId;
            $models[] = $userIngredient;
        }

        $si = $request->user()->shelfIngredients()->saveMany($models);

        return UserIngredientResource::collection($si);
    }

    public function delete(Request $request, int $ingredientId): Response
    {
        try {
            UserIngredient::where('user_id', $request->user()->id)
                ->where('ingredient_id', $ingredientId)
                ->delete();
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        return response(null, 204);
    }
}
