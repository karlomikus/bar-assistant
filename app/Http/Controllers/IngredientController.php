<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\IngredientCategory;
use Kami\Cocktail\Http\Resources\IngredientResource;
use Kami\Cocktail\Http\Resources\IngredientCategoryResource;

class IngredientController extends Controller
{
    public function index(Request $request)
    {
        $ingredients = Ingredient::with('category')->orderBy('name');

        if ($request->has('category_id')) {
            $ingredients->where('ingredient_category_id', $request->get('category_id'));
        }

        return IngredientResource::collection($ingredients->get());
    }

    public function show(int $id)
    {
        $ingredient = Ingredient::find($id);

        return new IngredientResource($ingredient);
    }

    public function categories()
    {
        $categories = IngredientCategory::all();

        return IngredientCategoryResource::collection($categories);
    }
}
