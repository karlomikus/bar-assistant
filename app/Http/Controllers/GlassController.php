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

class GlassController extends Controller
{
    public function index(): JsonResource
    {
        $glasses = Glass::orderBy('name')->withCount('cocktails')->filterByBar()->get();

        return GlassResource::collection($glasses);
    }

    public function show(Request $request, int $id): JsonResource
    {
        $glass = Glass::withCount('cocktails')->findOrFail($id);

        if (!$request->user()->isBarOwner($glass->bar)) {
            abort(403);
        }

        return new GlassResource($glass);
    }

    public function store(GlassRequest $request): JsonResponse
    {
        if (!$request->user()->isBarOwner(bar())) {
            abort(403);
        }

        $glass = new Glass();
        $glass->name = $request->post('name');
        $glass->description = $request->post('description');
        $glass->bar_id = bar()->id;
        $glass->created_user_id = $request->user()->id;
        $glass->save();

        return (new GlassResource($glass))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('glasses.show', $glass->id));
    }

    public function update(int $id, GlassRequest $request): JsonResource
    {
        $glass = Glass::findOrFail($id);

        if (!$request->user()->isBarOwner($glass->bar)) {
            abort(403);
        }

        $glass->name = $request->post('name');
        $glass->description = $request->post('description');
        $glass->updated_user_id = $request->user()->id;
        $glass->updated_at = now();
        $glass->save();

        $glass->cocktails->each(fn ($cocktail) => $cocktail->searchable());

        return new GlassResource($glass);
    }

    public function delete(Request $request, int $id): Response
    {
        $glass = Glass::findOrFail($id);

        if (!$request->user()->isBarOwner($glass->bar)) {
            abort(403);
        }

        $glass->delete();

        return response(null, 204);
    }

    public function find(Request $request): JsonResource
    {
        $name = $request->get('name');

        $glass = Glass::whereRaw('lower(name) = ?', [strtolower($name)])->filterByBar()->firstOrFail();

        return new GlassResource($glass);
    }
}
