<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Response;
use Kami\Cocktail\Models\Tag;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Http\Requests\TagRequest;
use Kami\Cocktail\Http\Resources\TagResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TagController extends Controller
{
    public function index(): JsonResource
    {
        $tags = Tag::orderBy('name')->get();

        return TagResource::collection($tags);
    }

    public function show(int $id): JsonResource
    {
        $tag = Tag::findOrFail($id);

        return new TagResource($tag);
    }

    public function store(TagRequest $request): JsonResponse
    {
        $tag = new Tag();
        $tag->name = $request->post('name');
        $tag->save();

        return (new TagResource($tag))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('tags.show', $tag->id));
    }

    public function update(TagRequest $request, int $id): JsonResource
    {
        $tag = Tag::findOrFail($id);
        $tag->name = $request->post('name');
        $tag->save();

        return new TagResource($tag);
    }

    public function delete(int $id): Response
    {
        Tag::findOrFail($id)->delete();

        return response(null, 204);
    }
}
