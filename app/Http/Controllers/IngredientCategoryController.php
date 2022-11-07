<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Kami\Cocktail\Models\IngredientCategory;
use Kami\Cocktail\Http\Resources\IngredientCategoryResource;

class IngredientCategoryController extends Controller
{
    public function index()
    {
        $categories = IngredientCategory::all();

        return IngredientCategoryResource::collection($categories);
    }
}
