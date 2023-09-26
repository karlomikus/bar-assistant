<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Models\CocktailMethod;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\CocktailMethodRequest;
use Kami\Cocktail\Http\Resources\CocktailMethodResource;

class CocktailMethodController extends Controller
{
    public function index(): JsonResource
    {
        $methods = CocktailMethod::orderBy('id')->withCount('cocktails')->filterByBar()->get();

        return CocktailMethodResource::collection($methods);
    }

    public function show(Request $request, int $id): JsonResource
    {
        $method = CocktailMethod::withCount('cocktails')->findOrFail($id);

        if ($request->user()->cannot('show', $method)) {
            abort(403);
        }

        return new CocktailMethodResource($method);
    }

    public function store(CocktailMethodRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', CocktailMethod::class)) {
            abort(403);
        }

        $method = new CocktailMethod();
        $method->name = $request->post('name');
        $method->dilution_percentage = (int) $request->post('dilution_percentage');
        $method->bar_id = bar()->id;
        $method->save();

        return (new CocktailMethodResource($method))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('cocktail-methods.show', $method->id));
    }

    public function update(CocktailMethodRequest $request, int $id): JsonResource
    {
        $method = CocktailMethod::findOrFail($id);

        if ($request->user()->cannot('edit', $method)) {
            abort(403);
        }

        $method->name = $request->post('name');
        $method->dilution_percentage = (int) $request->post('dilution_percentage');
        $method->updated_at = now();
        $method->save();

        return new CocktailMethodResource($method);
    }

    public function delete(Request $request, int $id): Response
    {
        $method = CocktailMethod::findOrFail($id);

        if ($request->user()->cannot('delete', $method)) {
            abort(403);
        }

        $method->delete();

        return response(null, 204);
    }
}
