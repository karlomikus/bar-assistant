<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\Uid\Ulid;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\DataObjects\Ingredient;
use Kami\Cocktail\Services\CocktailService;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\CocktailRequest;
use Kami\Cocktail\Http\Resources\CocktailResource;
use Kami\Cocktail\Http\Resources\SuccessActionResource;
use Kami\Cocktail\Http\Resources\CocktailPublicResource;

class CocktailController extends Controller
{
    /**
     * List all cocktails
     * - Paginated by X items
     * Optional query strings:
     * - user_id -> Filter by user id
     * - favorites -> Filter by user favorites
     */
    public function index(Request $request): JsonResource
    {
        $cocktails = Cocktail::with('ingredients.ingredient', 'images', 'tags', 'method');

        $perPage = $request->get('per_page', 15);

        if ($request->has('user_id')) {
            $cocktails->where('user_id', $request->get('user_id'));
        }

        if ($request->has('favorites')) {
            $cocktails->whereIn('id', function ($query) use ($request) {
                $query->select('cocktail_id')->from('cocktail_favorites')->where('user_id', $request->user()->id);
            });
        }

        if ($request->has('order_by')) {
            [$col, $order] = explode(':', $request->get('order_by', ''));
            if (in_array($col, ['created_at', 'name'])) {
                $cocktails->orderBy($col, $order);
            }
        } else {
            $cocktails->orderBy('name');
        }

        return CocktailResource::collection($cocktails->paginate($perPage));
    }

    /**
     * Return a single random cocktail
     */
    public function random(): JsonResource
    {
        $cocktail = Cocktail::inRandomOrder()
            ->firstOrFail()
            ->load('ingredients.ingredient', 'images', 'tags', 'method');

        return new CocktailResource($cocktail);
    }

    /**
     * Show a single cocktail by it's id or URL slug
     */
    public function show(int|string $idOrSlug): JsonResource
    {
        $cocktail = Cocktail::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail()
            ->load('ingredients.ingredient', 'images', 'tags', 'glass', 'ingredients.substitutes', 'method');

        return new CocktailResource($cocktail);
    }

    /**
     * Create a new cocktail
     */
    public function store(CocktailService $cocktailService, CocktailRequest $request): JsonResponse
    {
        $ingredients = [];
        foreach ($request->post('ingredients') as $formIngredient) {
            $ingredient = new Ingredient(
                $formIngredient['ingredient_id'],
                '',
                $formIngredient['amount'],
                $formIngredient['units'],
                $formIngredient['sort'],
                $formIngredient['optional'] ?? false,
                $formIngredient['substitutes'] ?? [],
            );
            $ingredients[] = $ingredient;
        }

        try {
            $cocktail = $cocktailService->createCocktail(
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
                $request->post('cocktail_method_id') ? (int) $request->post('cocktail_method_id') : null,
            );
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        $cocktail->load('ingredients.ingredient', 'images', 'tags', 'glass', 'ingredients.substitutes');

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
            $ingredient = new Ingredient(
                $formIngredient['ingredient_id'],
                '',
                $formIngredient['amount'],
                $formIngredient['units'],
                $formIngredient['sort'],
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
                $request->post('cocktail_method_id') ? (int) $request->post('cocktail_method_id') : null,
            );
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        $cocktail->load('ingredients.ingredient', 'images', 'tags', 'glass', 'ingredients.substitutes');

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
     * Show all cocktails that current user can make with
     * the ingredients he added to his shelf
     */
    public function userShelf(CocktailService $cocktailService, Request $request): JsonResource|JsonResponse
    {
        $limit = $request->has('limit') ? (int) $request->get('limit') : null;

        $cocktailIds = $cocktailService->getCocktailsByUserIngredients($request->user()->id, $limit);

        if ($request->has('format')) {
            return response()->json([
                'data' => $cocktailIds
            ]);
        }

        return CocktailResource::collection(
            Cocktail::orderBy('name')->find($cocktailIds)->load('ingredients.ingredient', 'images', 'tags')
        );
    }

    /**
     * Favorite a cocktail by id
     */
    public function toggleFavorite(CocktailService $cocktailService, Request $request, int $id): JsonResource
    {
        $isFavorite = $cocktailService->toggleFavorite($request->user(), $id);

        return new SuccessActionResource((object) ['id' => $id, 'is_favorited' => $isFavorite]);
    }

    /**
     * Show all cocktails that current user added to his favorites
     */
    public function userFavorites(Request $request): JsonResource
    {
        $cocktails = $request->user()
            ->favorites()
            ->with('cocktail.ingredients.ingredient', 'cocktail.images', 'cocktail.tags')
            ->orderBy('updated_at', 'desc')
            ->limit($request->get('limit', 100))
            ->get()
            ->pluck('cocktail');

        return CocktailResource::collection($cocktails);
    }

    public function makePublic(int|string $idOrSlug): JsonResource
    {
        $publicUlid = new Ulid();
        $cocktail = Cocktail::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail();

        if ($cocktail->public_id) {
            return new CocktailPublicResource($cocktail);
        }

        $cocktail->public_id = $publicUlid;
        $cocktail->public_at = now();
        $cocktail->public_expires_at = null;
        $cocktail->save();

        return new CocktailPublicResource($cocktail);
    }
}
