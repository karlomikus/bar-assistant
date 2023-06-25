<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Http\Requests\UstensilRequest;
use Kami\Cocktail\Http\Resources\UstensilResource;
use Kami\Cocktail\Models\Ustensil;
use Illuminate\Http\Resources\Json\JsonResource;

class UstensilsController extends Controller
{
    public function index(): JsonResource
    {
        $ustensils = Ustensil::orderBy('name')->get();

        return UstensilResource::collection($ustensils);
    }

    public function show(int $id): JsonResource
    {
        $ustensil = Ustensil::findOrFail($id);

        return new UstensilResource($ustensil);
    }

    public function store(UstensilRequest $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $ustensil = new Ustensil();
        $ustensil->name = $request->post('name');
        $ustensil->description = $request->post('description');
        $ustensil->save();

        return (new UstensilResource($ustensil))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('ustensils.show', $ustensil->id));
    }

    public function update(int $id, UstensilRequest $request): JsonResource
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $ustensil = Ustensil::findOrFail($id);
        $ustensil->name = $request->post('name');
        $ustensil->description = $request->post('description');
        $ustensil->save();

        $ustensil->cocktails->each(fn ($cocktail) => $cocktail->searchable());

        return new UstensilResource($ustensil);
    }

    public function delete(Request $request, int $id): Response
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        Ustensil::findOrFail($id)->delete();

        return response(null, 204);
    }

    public function find(Request $request): JsonResource
    {
        $name = $request->get('name');

        $ustensil = Ustensil::whereRaw('lower(name) = ?', [strtolower($name)])->firstOrFail();

        return new UstensilResource($ustensil);
    }
}
