<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Http\Resources\IngredientResource;
use Kami\Cocktail\Http\Resources\IngredientCategoryResource;
use Kami\Cocktail\Models\IngredientCategory;

class IngredientController extends Controller
{
    public function index()
    {
        $ingredients = Ingredient::orderBy('name')->paginate(30);

        return IngredientResource::collection($ingredients);
    }

    public function categories()
    {
        $categories = IngredientCategory::all();

        return IngredientCategoryResource::collection($categories);
    }
}
