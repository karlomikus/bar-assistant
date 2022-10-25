<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Kami\Cocktail\Models\UserIngredient;
use Kami\Cocktail\Http\Resources\ErrorResource;
use Kami\Cocktail\Http\Resources\SuccessActionResource;
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

    public function batch(Request $request)
    {
        // TODO Toggle
        $ingredientIds = $request->post('ids');

        $models = [];
        foreach ($ingredientIds as $ingId) {
            $userIngredient = new UserIngredient();
            $userIngredient->ingredient_id = $ingId;
            $models[] = $userIngredient;
        }

        $si = $request->user()->shelfIngredients()->saveMany($models);

        return UserIngredientResource::collection($si);
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

        return new SuccessActionResource((object) ['id' => $ingredientId]);
    }
}
