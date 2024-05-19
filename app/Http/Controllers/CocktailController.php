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
use Intervention\Image\ImageManager;
use Kami\Cocktail\DTO\Image as ImageDTO;
use Kami\Cocktail\Services\ImageService;
use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\Services\CocktailService;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\CocktailRequest;
use Kami\Cocktail\Repository\CocktailRepository;
use Kami\Cocktail\Http\Resources\CocktailResource;
use Kami\Cocktail\Http\Filters\CocktailQueryFilter;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Kami\Cocktail\DTO\Cocktail\Cocktail as CocktailDTO;
use Kami\Cocktail\External\Cocktail as CocktailExternal;
use Kami\Cocktail\Http\Resources\CocktailPublicResource;
use Kami\Cocktail\DTO\Cocktail\Ingredient as IngredientDTO;
use Kami\Cocktail\DTO\Cocktail\Substitute as SubstituteDTO;

class CocktailController extends Controller
{
    public function index(CocktailRepository $cocktailRepo, Request $request): JsonResource
    {
        try {
            $cocktails = new CocktailQueryFilter($cocktailRepo);
        } catch (InvalidFilterQuery $e) {
            abort(400, $e->getMessage());
        }

        $cocktails = $cocktails->paginate($request->get('per_page', 25))->withQueryString();

        return CocktailResource::collection($cocktails);
    }

    public function show(string $idOrSlug, Request $request): JsonResource
    {
        $cocktail = Cocktail::where('slug', $idOrSlug)
            ->orWhere('id', $idOrSlug)
            ->withRatings($request->user()->id)
            ->firstOrFail()
            ->load(['ingredients.ingredient', 'images' => function ($query) {
                $query->orderBy('sort');
            }, 'tags', 'glass', 'ingredients.substitutes', 'method', 'createdUser', 'updatedUser', 'collections', 'utensils']);

        if ($request->user()->cannot('show', $cocktail)) {
            abort(403);
        }

        return new CocktailResource($cocktail);
    }

    public function store(CocktailService $cocktailService, CocktailRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', Cocktail::class)) {
            abort(403);
        }

        $cocktailDTO = CocktailDTO::fromIlluminateRequest($request, bar()->id);

        try {
            $cocktail = $cocktailService->createCocktail($cocktailDTO);
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        $cocktail->load(['ingredients.ingredient', 'images' => function ($query) {
            $query->orderBy('sort');
        }, 'tags', 'glass', 'ingredients.substitutes', 'method', 'createdUser', 'updatedUser', 'collections', 'utensils']);

        return (new CocktailResource($cocktail))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('cocktails.show', $cocktail->id));
    }

    public function update(CocktailService $cocktailService, CocktailRequest $request, int $id): JsonResource
    {
        $cocktail = Cocktail::findOrFail($id);

        if ($request->user()->cannot('edit', $cocktail)) {
            abort(403);
        }

        $cocktailDTO = CocktailDTO::fromIlluminateRequest($request, $cocktail->bar_id);

        try {
            $cocktail = $cocktailService->updateCocktail($id, $cocktailDTO);
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        $cocktail->load(['ingredients.ingredient', 'images' => function ($query) {
            $query->orderBy('sort');
        }, 'tags', 'glass', 'ingredients.substitutes', 'method', 'createdUser', 'updatedUser', 'collections', 'utensils']);

        return new CocktailResource($cocktail);
    }

    public function delete(Request $request, int $id): Response
    {
        $cocktail = Cocktail::findOrFail($id);

        if ($request->user()->cannot('delete', $cocktail)) {
            abort(403);
        }

        $cocktail->delete();

        return response(null, 204);
    }

    public function toggleFavorite(CocktailService $cocktailService, Request $request, int $id): JsonResponse
    {
        $userFavorite = $cocktailService->toggleFavorite($request->user(), $id);

        return response()->json([
            'data' => ['id' => $id, 'is_favorited' => $userFavorite !== null]
        ]);
    }

    public function makePublic(Request $request, string $idOrSlug): JsonResource
    {
        $cocktail = Cocktail::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail();

        if ($request->user()->cannot('sharePublic', $cocktail)) {
            abort(403);
        }

        if ($cocktail->public_id) {
            return new CocktailPublicResource($cocktail);
        }

        $cocktail = $cocktail->makePublic(now());

        return new CocktailPublicResource($cocktail);
    }

    public function makePrivate(Request $request, string $idOrSlug): Response
    {
        $cocktail = Cocktail::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail();

        if ($request->user()->cannot('edit', $cocktail)) {
            abort(403);
        }

        $cocktail = $cocktail->makePrivate();

        return response(null, 204);
    }

    public function share(Request $request, string $idOrSlug): Response
    {
        $cocktail = Cocktail::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail()
            ->load(['ingredients.ingredient', 'images' => function ($query) {
                $query->orderBy('sort');
            }, 'ingredients.substitutes', 'ingredients.ingredient.category']);

        if ($request->user()->cannot('show', $cocktail)) {
            abort(403);
        }

        $type = $request->get('type', 'json');
        $units = Units::tryFrom($request->get('units', ''));

        $data = CocktailExternal::fromModel($cocktail)->toArray();

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
            return new Response(
                view('recipe_text_template', compact('cocktail', 'units'))->render(),
                200,
                ['Content-Type' => 'plain/text']
            );
        }

        if ($type === 'markdown' || $type === 'md') {
            return new Response(
                view('md_recipe_template', compact('cocktail', 'units'))->render(),
                200,
                ['Content-Type' => 'text/markdown']
            );
        }

        abort(400, 'Requested type "' . $type . '" not supported');
    }

    public function similar(CocktailRepository $cocktailRepo, Request $request, string $idOrSlug): JsonResource
    {
        $cocktail = Cocktail::where('id', $idOrSlug)->orWhere('slug', $idOrSlug)->with('ingredients')->firstOrFail();

        if ($request->user()->cannot('show', $cocktail)) {
            abort(403);
        }

        $relatedCocktails = $cocktailRepo->getSimilarCocktails($cocktail, $request->get('limit', 5));

        return CocktailResource::collection($relatedCocktails);
    }

    public function copy(string $idOrSlug, CocktailService $cocktailService, ImageService $imageservice, Request $request): JsonResponse
    {
        $cocktail = Cocktail::where('slug', $idOrSlug)
            ->orWhere('id', $idOrSlug)
            ->firstOrFail()
            ->load(['ingredients.ingredient', 'images', 'tags', 'ingredients.substitutes', 'utensils']);

        if ($request->user()->cannot('show', $cocktail) || $request->user()->cannot('create', Cocktail::class)) {
            abort(403);
        }

        // Copy images
        $manager = ImageManager::imagick();
        $imageDTOs = [];
        foreach ($cocktail->images as $image) {
            try {
                $imageDTOs[] = new ImageDTO(
                    $manager->read($image->getPath()),
                    $image->copyright,
                    $image->sort,
                );
            } catch (Throwable $e) {
            }
        }

        $images = array_map(
            fn ($image) => $image->id,
            $imageservice->uploadAndSaveImages($imageDTOs, $request->user()->id)
        );

        // Copy ingredients
        $ingredients = [];
        foreach ($cocktail->ingredients as $ingredient) {
            $substitutes = [];
            foreach ($ingredient->substitutes as $sub) {
                $substitutes[] = new SubstituteDTO(
                    $sub->ingredient_id,
                    $sub->amount,
                    $sub->amount_max,
                    $sub->units,
                );
            }

            $ingredient = new IngredientDTO(
                $ingredient->ingredient_id,
                null,
                $ingredient->amount,
                $ingredient->units,
                $ingredient->sort,
                $ingredient->optional,
                $substitutes,
                $ingredient->amount_max,
                $ingredient->note
            );
            $ingredients[] = $ingredient;
        }

        $cocktailDTO = new CocktailDTO(
            $cocktail->name . ' Copy',
            $cocktail->instructions,
            $request->user()->id,
            $cocktail->bar_id,
            $cocktail->description,
            $cocktail->source,
            $cocktail->garnish,
            $cocktail->glass_id,
            $cocktail->cocktail_method_id,
            $cocktail->tags->pluck('name')->toArray(),
            $ingredients,
            $images,
            $cocktail->utensils->pluck('id')->toArray(),
        );

        try {
            $cocktail = $cocktailService->createCocktail($cocktailDTO);
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        $cocktail->load(['ingredients.ingredient', 'images' => function ($query) {
            $query->orderBy('sort');
        }, 'tags', 'glass', 'ingredients.substitutes', 'method', 'createdUser', 'updatedUser', 'collections', 'utensils']);

        return (new CocktailResource($cocktail))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('cocktails.show', $cocktail->id));
    }
}
