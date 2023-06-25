<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Services\CocktailService;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\CocktailRequest;
use Kami\Cocktail\Http\Resources\CocktailResource;
use Kami\Cocktail\Http\Filters\CocktailQueryFilter;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Kami\Cocktail\Http\Resources\SuccessActionResource;
use Kami\Cocktail\Http\Resources\CocktailPublicResource;
use Kami\Cocktail\DataObjects\Cocktail\Cocktail as CocktailDTO;
use Kami\Cocktail\DataObjects\Cocktail\Ingredient as IngredientDTO;

class CocktailController extends Controller
{
    /**
     * List all cocktails
     */
    public function index(CocktailService $cocktailService, Request $request): JsonResource
    {
        try {
            $cocktails = new CocktailQueryFilter($cocktailService);
        } catch (InvalidFilterQuery $e) {
            abort(400, $e->getMessage());
        }

        $cocktails = $cocktails->paginate($request->get('per_page', 15));

        return CocktailResource::collection($cocktails);
    }

    /**
     * Show a single cocktail by it's id or URL slug
     */
    public function show(int|string $idOrSlug, Request $request): JsonResource
    {
        $cocktail = Cocktail::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->withRatings($request->user()->id)
            ->firstOrFail()
            ->load(['ingredients.ingredient', 'images' => function ($query) {
                $query->orderBy('sort');
            }, 'tags', 'glass', 'ingredients.substitutes', 'method', 'notes']);

        return new CocktailResource($cocktail);
    }

    /**
     * Create a new cocktail
     */
    public function store(CocktailService $cocktailService, CocktailRequest $request): JsonResponse
    {
        $ingredients = [];
        foreach ($request->post('ingredients') as $formIngredient) {
            $ingredient = new IngredientDTO(
                (int) $formIngredient['ingredient_id'],
                null,
                (float) $formIngredient['amount'],
                $formIngredient['units'],
                (int) $formIngredient['sort'],
                $formIngredient['optional'] ?? false,
                $formIngredient['substitutes'] ?? [],
            );
            $ingredients[] = $ingredient;
        }

        $cocktailDTO = new CocktailDTO(
            $request->post('name'),
            $request->post('instructions'),
            $request->user()->id,
            $request->post('description'),
            $request->post('source'),
            $request->post('garnish'),
            $request->post('glass_id') ? (int) $request->post('glass_id') : null,
            $request->post('cocktail_method_id') ? (int) $request->post('cocktail_method_id') : null,
            $request->post('tags', []),
            $request->post('ustensils', []),
            $ingredients,
            $request->post('images', []),
        );

        try {
            $cocktail = $cocktailService->createCocktail($cocktailDTO);
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        $cocktail->load('ingredients.ingredient', 'images', 'tags', 'glass', 'ingredients.substitutes', 'ustensils');

        return (new CocktailResource($cocktail))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('cocktails.show', $cocktail->id));
    }

    /**
     * Update a single cocktail by id
     */
    public function update(CocktailService $cocktailService, CocktailRequest $request, int $id): JsonResource
    {
        $cocktail = Cocktail::findOrFail($id);

        if ($request->user()->cannot('edit', $cocktail)) {
            abort(403);
        }

        $ingredients = [];
        foreach ($request->post('ingredients') as $formIngredient) {
            $ingredient = new IngredientDTO(
                (int) $formIngredient['ingredient_id'],
                null,
                (float) $formIngredient['amount'],
                $formIngredient['units'],
                (int) $formIngredient['sort'],
                $formIngredient['optional'] ?? false,
                $formIngredient['substitutes'] ?? [],
            );
            $ingredients[] = $ingredient;
        }

        try {
            $cocktail = $cocktailService->updateCocktail(
                $id,
                $request->post('name'),
                $request->post('instructions'),
                $ingredients,
                $request->user()->id,
                $request->post('description'),
                $request->post('garnish'),
                $request->post('source'),
                $request->post('images', []),
                $request->post('tags', []),
                $request->post('glass_id') ? (int) $request->post('glass_id') : null,
                $request->post('ustensils', []),
                $request->post('cocktail_method_id') ? (int) $request->post('cocktail_method_id') : null,
            );
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        $cocktail->load('ingredients.ingredient', 'images', 'tags', 'glass', 'ingredients.substitutes', 'ustensils');

        return new CocktailResource($cocktail);
    }

    /**
     * Delete a single cocktail by id
     */
    public function delete(Request $request, int $id): Response
    {
        $cocktail = Cocktail::findOrFail($id);

        if ($request->user()->cannot('delete', $cocktail)) {
            abort(403);
        }

        $cocktail->delete();

        return response(null, 204);
    }

    /**
     * Favorite a cocktail by id
     */
    public function toggleFavorite(CocktailService $cocktailService, Request $request, int $id): JsonResource
    {
        $isFavorite = $cocktailService->toggleFavorite($request->user(), $id);

        return new SuccessActionResource((object) ['id' => $id, 'is_favorited' => $isFavorite]);
    }

    public function makePublic(int|string $idOrSlug): JsonResource
    {
        $cocktail = Cocktail::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail();

        if ($cocktail->public_id) {
            return new CocktailPublicResource($cocktail);
        }

        $cocktail = $cocktail->makePublic(now());

        return new CocktailPublicResource($cocktail);
    }

    public function makePrivate(int|string $idOrSlug): Response
    {
        $cocktail = Cocktail::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail();

        $cocktail = $cocktail->makePrivate();

        return response(null, 204);
    }
}
