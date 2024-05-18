<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Kami\RecipeUtils\UnitConverter\Units;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\External\Export\CocktailsToCSV;
use Kami\Cocktail\Http\Requests\CollectionRequest;
use Kami\Cocktail\Http\Resources\CollectionResource;
use Kami\Cocktail\Http\Filters\CollectionQueryFilter;
use Kami\Cocktail\Models\Collection as CocktailCollection;
use Kami\Cocktail\External\Collection as CollectionExternal;

class CollectionController extends Controller
{
    public function index(): JsonResource
    {
        $collections = (new CollectionQueryFilter())->get();

        return CollectionResource::collection($collections);
    }

    public function shared(): JsonResource
    {
        $collections = CocktailCollection::where('is_bar_shared', true)
            ->select('collections.*')
            ->join('bar_memberships', 'bar_memberships.id', '=', 'collections.bar_membership_id')
            ->where('bar_memberships.bar_id', bar()->id)
            ->orderBy('name')
            ->with('cocktails', 'barMembership.user')
            ->get();

        return CollectionResource::collection($collections);
    }

    public function show(Request $request, int $id): JsonResource
    {
        $collection = CocktailCollection::findOrFail($id);

        if ($request->user()->cannot('show', $collection)) {
            abort(403);
        }

        return new CollectionResource($collection);
    }

    public function store(CollectionRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', CocktailCollection::class)) {
            abort(403);
        }

        $barMembership = $request->user()->getBarMembership(bar()->id);

        $collection = new CocktailCollection();
        $collection->name = $request->post('name');
        $collection->description = $request->post('description');
        $collection->bar_membership_id = $barMembership->id;
        $collection->is_bar_shared = (bool) $request->post('is_bar_shared');
        $collection->save();

        $cocktailIds = $request->post('cocktails', []);
        if (!empty($cocktailIds)) {
            $cocktails = DB::table('cocktails')
                ->select('id')
                ->where('bar_id', $barMembership->bar_id)
                ->whereIn('id', $cocktailIds)
                ->pluck('id');
            $collection->cocktails()->attach($cocktails);
        }

        return (new CollectionResource($collection))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('collection.show', $collection->id));
    }

    public function update(CollectionRequest $request, int $id): JsonResource
    {
        $collection = CocktailCollection::findOrFail($id);

        if ($request->user()->cannot('edit', $collection)) {
            abort(403);
        }

        $collection->name = $request->post('name');
        $collection->description = $request->post('description');
        $collection->updated_at = now();
        $collection->is_bar_shared = (bool) $request->post('is_bar_shared');
        $collection->save();

        return new CollectionResource($collection);
    }

    public function cocktails(Request $request, int $id): JsonResource
    {
        $collection = CocktailCollection::findOrFail($id);

        if ($request->user()->cannot('edit', $collection)) {
            abort(403);
        }

        $cocktailIds = $request->post('cocktails', []);

        try {
            if (!empty($cocktailIds)) {
                $cocktails = DB::table('cocktails')
                    ->select('id')
                    ->where('bar_id', $collection->barMembership->bar_id)
                    ->whereIn('id', $cocktailIds)
                    ->pluck('id');
                $collection->cocktails()->syncWithoutDetaching($cocktails);
                $collection->updated_at = now();
                $collection->save();
            }
        } catch (Throwable) {
            abort(500, 'Unable to add cocktails to collection!');
        }

        return new CollectionResource($collection);
    }

    public function cocktail(Request $request, int $id, int $cocktailId): JsonResource
    {
        $collection = CocktailCollection::findOrFail($id);

        if ($request->user()->cannot('edit', $collection)) {
            abort(403);
        }

        $cocktail = Cocktail::where('id', $cocktailId)->where('bar_id', $collection->barMembership->bar_id)->firstOrFail();

        try {
            $cocktail->addToCollection($collection);
            $collection->updated_at = now();
            $collection->save();
        } catch (Throwable) {
            abort(500, 'Unable to add cocktail to collection!');
        }

        return new CollectionResource($collection);
    }

    public function delete(Request $request, int $id): Response
    {
        $collection = CocktailCollection::findOrFail($id);

        if ($request->user()->cannot('delete', $collection)) {
            abort(403);
        }

        $collection->delete();

        return response(null, 204);
    }

    public function deleteResourceFromCollection(Request $request, int $id, int $cocktailId): Response
    {
        $collection = CocktailCollection::findOrFail($id);

        if ($request->user()->cannot('edit', $collection)) {
            abort(403);
        }

        try {
            $collection->cocktails()->detach($cocktailId);
            $collection->updated_at = now();
            $collection->save();
        } catch (Throwable $e) {
            abort(500, 'Unable to remove cocktail from collection!');
        }

        return response(null, 204);
    }

    public function share(Request $request, int $id): Response
    {
        $type = $request->get('type', 'json');
        $units = Units::tryFrom($request->get('units', ''));

        $collection = CocktailCollection::findOrFail($id);

        if ($request->user()->cannot('show', $collection)) {
            abort(403);
        }

        $collection->load('cocktails.glass', 'cocktails.method', 'cocktails.images', 'cocktails.tags', 'cocktails.ingredients.ingredient.category', 'cocktails.ingredients.substitutes');

        $data = CollectionExternal::fromModel($collection)->toArray();

        if ($type === 'json') {
            return new Response(json_encode($data, JSON_UNESCAPED_UNICODE), 200, ['Content-Type' => 'application/json']);
        }

        if ($type === 'yaml' || $type === 'yml') {
            return new Response(Yaml::dump($data, 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK), 200, ['Content-Type' => 'application/yaml']);
        }

        if ($type === 'csv') {
            $csv = new CocktailsToCSV($units);
            $csvResult = $csv->process($collection->cocktails);

            return new Response($csvResult, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="' . Str::slug($collection->name) . '.csv"']);
        }

        abort(400, 'Requested type "' . $type . '" not supported');
    }
}
