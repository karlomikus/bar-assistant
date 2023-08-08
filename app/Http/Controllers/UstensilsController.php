<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Http\Requests\UtensilRequest;
use Kami\Cocktail\Http\Resources\UtensilResource;
use Kami\Cocktail\Models\Utensil;
use Illuminate\Http\Resources\Json\JsonResource;

class UtensilsController extends Controller
{
    public function index(): JsonResource
    {
        $utensils = Utensil::orderBy('name')->get();

        return UtensilResource::collection($utensils);
    }

    public function show(int $id): JsonResource
    {
        $utensil = Utensil::findOrFail($id);

        return new UtensilResource($utensil);
    }

    public function store(UtensilRequest $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $utensil = new Utensil();
        $utensil->name = $request->post('name');
        $utensil->description = $request->post('description');
        $utensil->save();

        return (new UtensilResource($utensil))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('utensils.show', $utensil->id));
    }

    public function update(int $id, UtensilRequest $request): JsonResource
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $utensil = Utensil::findOrFail($id);
        $utensil->name = $request->post('name');
        $utensil->description = $request->post('description');
        $utensil->save();

        $utensil->cocktails->each(fn ($cocktail) => $cocktail->searchable());

        return new UtensilResource($utensil);
    }

    public function delete(Request $request, int $id): Response
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        Utensil::findOrFail($id)->delete();

        return response(null, 204);
    }

    public function find(Request $request): JsonResource
    {
        $name = $request->get('name');

        $utensil = Utensil::whereRaw('lower(name) = ?', [strtolower($name)])->firstOrFail();

        return new UtensilResource($utensil);
    }
}
