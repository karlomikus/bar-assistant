<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Models\Ingredient;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Kami\Cocktail\Services\IngredientService;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\IngredientRequest;
use Kami\Cocktail\Http\Resources\IngredientResource;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;

class IngredientController extends Controller
{
    public function index(Request $request): JsonResource
    {
        try {
            $ingredients = QueryBuilder::for(Ingredient::class)
                ->with('category', 'images')
                ->withCount('cocktails')
                ->allowedFilters([
                    AllowedFilter::partial('name'),
                    AllowedFilter::exact('category_id', 'ingredient_category_id'),
                    AllowedFilter::exact('origin'),
                    AllowedFilter::callback('on_shopping_list', function ($query) use ($request) {
                        $usersList = $request->user()->shoppingLists->pluck('ingredient_id');
                        $query->whereIn('id', $usersList);
                    }),
                    AllowedFilter::callback('on_shelf', function ($query) use ($request) {
                        $query->join('user_ingredients', 'user_ingredients.ingredient_id', '=', 'ingredients.id')->where('user_ingredients.user_id', $request->user()->id);
                    }),
                ])
                ->defaultSort('name')
                ->allowedSorts('name', 'created_at')
                ->paginate($request->get('per_page', 50));
        } catch (InvalidFilterQuery $e) {
            abort(400, $e->getMessage());
        }

        return IngredientResource::collection($ingredients);
    }

    public function show(int|string $id): JsonResource
    {
        $ingredient = Ingredient::with('cocktails', 'images', 'varieties', 'parentIngredient')
            ->where('id', $id)
            ->orWhere('slug', $id)
            ->firstOrFail();

        return new IngredientResource($ingredient);
    }

    public function store(IngredientService $ingredientService, IngredientRequest $request): JsonResponse
    {
        $ingredient = $ingredientService->createIngredient(
            $request->post('name'),
            (int) $request->post('ingredient_category_id'),
            auth()->user()->id,
            floatval($request->post('strength', '0')),
            $request->post('description'),
            $request->post('origin'),
            $request->post('color'),
            $request->post('parent_ingredient_id') ? (int) $request->post('parent_ingredient_id') : null,
            $request->post('images', [])
        );

        return (new IngredientResource($ingredient))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('ingredients.show', $ingredient->id));
    }

    public function update(IngredientService $ingredientService, IngredientRequest $request, int $id): JsonResource
    {
        $ingredient = Ingredient::findOrFail($id);

        if ($request->user()->cannot('edit', $ingredient)) {
            abort(403);
        }

        $ingredient = $ingredientService->updateIngredient(
            $id,
            $request->post('name'),
            (int) $request->post('ingredient_category_id'),
            auth()->user()->id,
            floatval($request->post('strength', '0')),
            $request->post('description'),
            $request->post('origin'),
            $request->post('color'),
            $request->post('parent_ingredient_id') ? (int) $request->post('parent_ingredient_id') : null,
            $request->post('images', [])
        );

        return new IngredientResource($ingredient);
    }

    public function delete(Request $request, int $id): Response
    {
        $ingredient = Ingredient::findOrFail($id);

        if ($request->user()->cannot('delete', $ingredient)) {
            abort(403);
        }

        $ingredient->delete();

        return response(null, 204);
    }

    public function find(Request $request): JsonResource
    {
        $name = $request->get('name');

        $ingredient = Ingredient::with('cocktails', 'images', 'varieties', 'parentIngredient')->whereRaw('lower(name) = ?', [strtolower($name)])->firstOrFail();

        return new IngredientResource($ingredient);
    }
}
