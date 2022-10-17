<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\IngredientCategory;
use Kami\Cocktail\Http\Resources\ErrorResource;
use Kami\Cocktail\Http\Resources\IngredientResource;
use Kami\Cocktail\Http\Resources\SuccessActionResource;
use Kami\Cocktail\Http\Resources\IngredientCategoryResource;

class IngredientController extends Controller
{
    public function index(Request $request)
    {
        $ingredients = Ingredient::with('category')->orderBy('name')->orderBy('ingredient_category_id');

        if ($request->has('category_id')) {
            $ingredients->where('ingredient_category_id', $request->get('category_id'));
        }

        return IngredientResource::collection($ingredients->get());
    }

    public function show(int|string $id)
    {
        $ingredient = Ingredient::where('id', $id)->orWhere('slug', $id)->first();

        return new IngredientResource($ingredient);
    }

    public function store(Request $request)
    {
        $ingredient = new Ingredient();
        $ingredient->name = $request->post('name');
        $ingredient->strength = floatval($request->post('strength'));
        $ingredient->description = $request->post('description');
        $ingredient->history = $request->post('history');
        $ingredient->origin = $request->post('origin');
        $ingredient->color = $request->post('color');
        $ingredient->ingredient_category_id = (int) $request->post('ingredient_category_id');
        $ingredient->parent_ingredient_id = $request->post('parent_ingredient_id') ? (int) $request->post('parent_ingredient_id') : null;
        $ingredient->save();

        return new IngredientResource($ingredient);
    }

    public function update(Request $request, int $id)
    {
        try {
            $ingredient = Ingredient::findOrFail($id);
        } catch(Throwable $e) {
            return new ErrorResource($e);
        }

        $ingredient->name = $request->post('name');
        $ingredient->strength = floatval($request->post('strength'));
        $ingredient->description = $request->post('description');
        $ingredient->history = $request->post('history');
        $ingredient->origin = $request->post('origin');
        $ingredient->color = $request->post('color');
        $ingredient->ingredient_category_id = (int) $request->post('ingredient_category_id');
        $ingredient->parent_ingredient_id = $request->post('parent_ingredient_id') ? (int) $request->post('parent_ingredient_id') : null;
        $ingredient->save();

        return new IngredientResource($ingredient);
    }

    public function delete(int $id)
    {
        try {
            Ingredient::findOrFail($id)->delete();
        } catch (Throwable $e) {
            return new ErrorResource($e);
        }

        return new SuccessActionResource((object) ['id' => $id]);
    }

    public function categories()
    {
        $categories = IngredientCategory::all();

        return IngredientCategoryResource::collection($categories);
    }
}
