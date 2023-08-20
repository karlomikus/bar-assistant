<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\UserShoppingList;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\SuccessActionResource;
use Kami\Cocktail\Http\Resources\UserShoppingListResource;

class ShoppingListController extends Controller
{
    public function index(Request $request): JsonResource
    {
        return UserShoppingListResource::collection(
            $request->user()->getBarMembership(bar()->id)->shoppingListIngredients->load('ingredient')
        );
    }

    public function batchStore(Request $request): JsonResource
    {
        $barMembership = $request->user()->getBarMembership(bar()->id);
        $ingredientIds = $request->post('ingredient_ids');

        $models = [];
        foreach ($ingredientIds as $ingId) {
            $usl = new UserShoppingList();
            $usl->ingredient_id = $ingId;
            $usl->bar_membership_id = $barMembership->id;
            try {
                $models[] = $barMembership->shoppingListIngredients()->save($usl);
            } catch (Throwable) {
            }
        }

        return UserShoppingListResource::collection($models);
    }

    public function batchDelete(Request $request): Response
    {
        $barMembership = $request->user()->getBarMembership(bar()->id);
        $ingredientIds = $request->post('ingredient_ids');

        try {
            $barMembership->shoppingListIngredients()->whereIn('ingredient_id', $ingredientIds)->delete();
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        return response(null, 204);
    }

    public function share(Request $request): Response
    {
        $barMembership = $request->user()->getBarMembership(bar()->id);
        $type = $request->get('type', 'markdown');

        $shoppingListIngredients = $barMembership
            ->shoppingListIngredients
            ->load('ingredient.category')
            ->groupBy('ingredient.category.name');

        if ($type === 'markdown' || $type === 'md') {
            return new Response(
                view('md_shopping_list_template', compact('shoppingListIngredients'))->render(),
                200,
                ['Content-Type' => 'text/markdown']
            );
        }

        abort(400, 'Requested type "' . $type . '" not supported');
    }
}
