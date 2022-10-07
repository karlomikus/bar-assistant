<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Kami\Cocktail\Models\UserIngredient;

class ShelfController extends Controller
{
    public function save(Request $request, int $ingredientId)
    {
        $userIngredient = new UserIngredient();
        $userIngredient->ingredient_id = $ingredientId;

        $request->user()->shelfIngredients()->save($userIngredient);
    }

    public function delete(Request $request, int $ingredientId)
    {
        
    }
}
