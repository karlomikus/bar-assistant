<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Models\Utensil;
use Kami\Cocktail\Http\Requests\UtensilRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\UtensilResource;

class UtensilsController extends Controller
{
    public function index(): JsonResource
    {
        $utensils = Utensil::orderBy('name')->filterByBar()->get();

        return UtensilResource::collection($utensils);
    }

    public function show(Request $request, int $id): JsonResource
    {
        $utensil = Utensil::findOrFail($id);

        if ($request->user()->cannot('show', $utensil)) {
            abort(403);
        }

        return new UtensilResource($utensil);
    }

    public function store(UtensilRequest $request): JsonResponse
    {
        if (!$request->user()->isBarAdmin(bar()->id)) {
            abort(403);
        }

        $utensil = new Utensil();
        $utensil->name = $request->post('name');
        $utensil->description = $request->post('description');
        $utensil->created_user_id = $request->user()->id;
        $utensil->bar_id = bar()->id;
        $utensil->save();

        return (new UtensilResource($utensil))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('utensils.show', $utensil->id));
    }

    public function update(int $id, UtensilRequest $request): JsonResource
    {
        $utensil = Utensil::findOrFail($id);

        if ($request->user()->cannot('edit', $utensil)) {
            abort(403);
        }

        $utensil->name = $request->post('name');
        $utensil->description = $request->post('description');
        $utensil->created_user_id = $request->user()->id;
        $utensil->updated_at = now();
        $utensil->save();

        return new UtensilResource($utensil);
    }

    public function delete(Request $request, int $id): Response
    {
        $utensil = Utensil::findOrFail($id);

        if ($request->user()->cannot('delete', $utensil)) {
            abort(403);
        }

        $utensil->delete();

        return response(null, 204);
    }
}
