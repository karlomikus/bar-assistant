<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Glass;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Http\Requests\GlassRequest;
use Kami\Cocktail\Http\Resources\GlassResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Filters\GlassQueryFilter;

class GlassController extends Controller
{
    public function index(): JsonResource
    {
        $glasses = (new GlassQueryFilter())->get();

        return GlassResource::collection($glasses);
    }

    public function show(Request $request, int $id): JsonResource
    {
        $glass = Glass::withCount('cocktails')->findOrFail($id);

        if ($request->user()->cannot('show', $glass)) {
            abort(403);
        }

        return new GlassResource($glass);
    }

    public function store(GlassRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', Glass::class)) {
            abort(403);
        }

        $glass = new Glass();
        $glass->name = $request->post('name');
        $glass->description = $request->post('description');
        $glass->volume = $request->float('volume');
        $glass->volume_units = $request->post('volume_units');
        $glass->bar_id = bar()->id;
        $glass->save();

        return (new GlassResource($glass))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('glasses.show', $glass->id));
    }

    public function update(int $id, GlassRequest $request): JsonResource
    {
        $glass = Glass::findOrFail($id);

        if ($request->user()->cannot('edit', $glass)) {
            abort(403);
        }

        $glass->name = $request->post('name');
        $glass->description = $request->post('description');
        $glass->volume = $request->float('volume');
        $glass->volume_units = $request->post('volume_units');
        $glass->updated_at = now();
        $glass->save();

        $glass->cocktails->each(fn ($cocktail) => $cocktail->searchable());

        return new GlassResource($glass);
    }

    public function delete(Request $request, int $id): Response
    {
        $glass = Glass::findOrFail($id);

        if ($request->user()->cannot('delete', $glass)) {
            abort(403);
        }

        $glass->delete();

        return response(null, 204);
    }
}
