<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\CollectionRequest;
use Kami\Cocktail\Http\Resources\CollectionResource;
use Kami\Cocktail\Models\Collection as CocktailCollection;

class CollectionController extends Controller
{
    public function index(Request $request): JsonResource
    {
        $collections = CocktailCollection::where('user_id', $request->user()->id)->get();

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
        $collection = new CocktailCollection();
        $collection->name = $request->post('name');
        $collection->description = $request->post('description');
        $collection->user_id = $request->user()->id;
        $collection->save();

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

        return new CollectionResource($collection);
    }

    public function cocktail(Request $request, int $id, int $cocktailId)
    {
        $collection = CocktailCollection::findOrFail($id);

        if ($request->user()->cannot('edit', $collection)) {
            abort(403);
        }

        $cocktail = Cocktail::findOrFail($cocktailId);

        // try {
            $cocktail->addToCollection($collection);
        // } catch (Throwable $e) {
        //     abort(500, 'Unable to add cocktail to collection!');
        // }

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
}
