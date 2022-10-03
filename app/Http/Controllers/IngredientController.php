<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Http\Resources\IngredientResource;

class IngredientController extends Controller
{
    public function index()
    {
        $ingredients = Ingredient::all();

        return IngredientResource::collection($ingredients);
    }
}
