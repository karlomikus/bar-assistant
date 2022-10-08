<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Kami\Cocktail\Models\UserIngredient;
use Kami\Cocktail\Http\Resources\ErrorResource;
use Kami\Cocktail\Http\Resources\DeleteSuccessResource;
use Kami\Cocktail\Http\Resources\UserIngredientResource;

class ShelfController extends Controller
{
    public function index(Request $request)
    {
        $userIngredients = $request->user()->shelfIngredients;

        return UserIngredientResource::collection($userIngredients);
    }

    public function save(Request $request, int $ingredientId)
    {
        $userIngredient = new UserIngredient();
        $userIngredient->ingredient_id = $ingredientId;

        $si = $request->user()->shelfIngredients()->save($userIngredient);

        return new UserIngredientResource($si);
    }

    public function delete(Request $request, int $ingredientId)
    {
        try {
            UserIngredient::where('user_id', $request->user()->id)
                ->where('ingredient_id', $ingredientId)
                ->delete();
        } catch (Throwable $e) {
            return new ErrorResource($e);
        }

        return new DeleteSuccessResource((object) ['id' => $ingredientId]);
    }
}
