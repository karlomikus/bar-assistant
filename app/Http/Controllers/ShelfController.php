<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\UserIngredient;
use Kami\Cocktail\Models\CocktailFavorite;
use Kami\Cocktail\Models\UserShoppingList;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Repository\CocktailRepository;
use Kami\Cocktail\Http\Requests\IngredientsBatchRequest;
use Kami\Cocktail\Http\Resources\UserIngredientResource;

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

    public function cocktails(CocktailRepository $cocktailRepo, Request $request): JsonResponse
    {
        $barMembership = $request->user()->getBarMembership(bar()->id);
        $limit = $request->has('limit') ? (int) $request->get('limit') : null;

        $cocktailIds = $cocktailRepo->getCocktailsByIngredients(
            $barMembership->userIngredients->pluck('ingredient_id')->toArray(),
            $limit,
            $barMembership->use_parent_as_substitute,
        );

        return response()->json([
            'data' => $cocktailIds
        ]);
    }

    public function favorites(Request $request): JsonResponse
    {
        $barMembership = $request->user()->getBarMembership(bar()->id);

        $cocktailIds = CocktailFavorite::where('bar_membership_id', $barMembership->id)->pluck('cocktail_id');

        return response()->json([
            'data' => $cocktailIds
        ]);
    }

    public function batchStore(IngredientsBatchRequest $request): JsonResource
    {
        $barMembership = $request->user()->getBarMembership(bar()->id);

        $ingredients = DB::table('ingredients')
            ->select('id')
            ->where('bar_id', $barMembership->bar_id)
            ->whereIn('id', $request->post('ingredient_ids'))
            ->pluck('id');

        // Let's remove ingredients from shopping list since they are on our shelf now
        UserShoppingList::whereIn('ingredient_id', $ingredients)->delete();

        $models = [];
        foreach ($ingredients as $dbIngredientId) {
            $userIngredient = new UserIngredient();
            $userIngredient->ingredient_id = $dbIngredientId;
            $models[] = $userIngredient;
        }

        $shelfIngredients = $barMembership->userIngredients()->saveMany($models);

        return UserIngredientResource::collection($shelfIngredients);
    }

    public function batchDelete(Request $request): Response
    {
        $barMembership = $request->user()->getBarMembership(bar()->id);

        $ingredients = DB::table('ingredients')
            ->select('id')
            ->where('bar_id', $barMembership->bar_id)
            ->whereIn('id', $request->post('ingredient_ids'))
            ->pluck('id');

        try {
            $barMembership->userIngredients()->whereIn('ingredient_id', $ingredients)->delete();
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        return response(null, 204);
    }
}
