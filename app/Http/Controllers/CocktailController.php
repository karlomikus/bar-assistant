<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Http\JsonResponse;
use Spatie\ArrayToXml\ArrayToXml;
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

        $cocktails = $cocktails->paginate($request->get('per_page', 15))->withQueryString();

        return CocktailResource::collection($cocktails);
    }

    /**
     * Show a single cocktail by it's id or URL slug
     */
    public function show(int|string $idOrSlug, Request $request): JsonResource
    {
        $cocktail = Cocktail::where('slug', $idOrSlug)
            ->orWhere('id', $idOrSlug)
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
            $ingredients,
            $request->post('images', []),
        );

        try {
            $cocktail = $cocktailService->createCocktail($cocktailDTO);
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

    public function share(Request $request, int|string $idOrSlug): Response
    {
        $type = $request->get('type', 'json');

        $cocktail = Cocktail::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail()
            ->load(['ingredients.ingredient', 'images' => function ($query) {
                $query->orderBy('sort');
            }, 'ingredients.substitutes']);

        $data = $cocktail->toShareableArray();

        if ($type === 'json') {
            return new Response(json_encode($data, JSON_UNESCAPED_UNICODE), 200, ['Content-Type' => 'application/json']);
        }

        if ($type === 'yaml' || $type === 'yml') {
            return new Response(Yaml::dump($data, 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK), 200, ['Content-Type' => 'application/yaml']);
        }

        if ($type === 'xml') {
            return new Response(ArrayToXml::convert($data, 'cocktail', xmlEncoding: 'UTF-8'), 200, ['Content-Type' => 'application/xml']);
        }

        if ($type === 'text') {
            return new Response($cocktail->toText(), 200, ['Content-Type' => 'plain/text']);
        }

        abort(400, 'Requested type "' . $type . '" not supported');
    }

    public function similar(Request $request, int|string $idOrSlug): JsonResource
    {
        $limitTotal = $request->get('limit', 5);

        $cocktail = Cocktail::where('id', $idOrSlug)->orWhere('slug', $idOrSlug)->firstOrFail();
        $ingredients = $cocktail->ingredients->filter(fn ($ci) => $ci->optional === false)->pluck('ingredient_id');

        $relatedCocktails = collect();
        while ($ingredients->count() > 0) {
            $ingredients->pop();
            $possibleRelatedCocktails = Cocktail::where('cocktails.id', '<>', $cocktail->id)
                ->whereIn('cocktails.id', function ($query) use ($ingredients) {
                    $query->select('ci.cocktail_id')
                        ->from('cocktail_ingredients AS ci')
                        ->whereIn('ci.ingredient_id', $ingredients)
                        ->where('optional', false)
                        ->groupBy('ci.cocktail_id')
                        ->havingRaw('COUNT(DISTINCT ci.ingredient_id) = ?', [$ingredients->count()]);
                })
                ->get();

            $relatedCocktails = $relatedCocktails->merge($possibleRelatedCocktails)->unique('id');
            if ($relatedCocktails->count() > $limitTotal) {
                $relatedCocktails = $relatedCocktails->take($limitTotal);
                break;
            }
        }

        return CocktailResource::collection($relatedCocktails);
    }
}
