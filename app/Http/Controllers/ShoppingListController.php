<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Kami\Cocktail\Models\UserShoppingList;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\SuccessActionResource;
use Kami\Cocktail\Http\Resources\UserShoppingListResource;

class ShoppingListController extends Controller
{
    public function index(Request $request): JsonResource
    {
        return UserShoppingListResource::collection(
            $request->user()->shoppingLists->load('ingredient')
        );
    }

    public function batchStore(Request $request): JsonResource
    {
        $ingredientIds = $request->post('ingredient_ids');

        $models = [];
        foreach ($ingredientIds as $ingId) {
            $usl = new UserShoppingList();
            $usl->ingredient_id = $ingId;
            try {
                $models[] = $request->user()->shoppingLists()->save($usl);
            } catch (Throwable $e) {
            }
        }

        return UserShoppingListResource::collection($models);
    }

    public function batchDelete(Request $request): JsonResource
    {
        $ingredientIds = $request->post('ingredient_ids');

        try {
            $request->user()->shoppingLists()->whereIn('ingredient_id', $ingredientIds)->delete();
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        return new SuccessActionResource((object) ['ingredient_ids' => $ingredientIds]);
    }
}
