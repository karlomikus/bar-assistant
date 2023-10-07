<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Http\Requests\TagRequest;
use Kami\Cocktail\Http\Resources\TagResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TagController extends Controller
{
    public function index(): JsonResource
    {
        $tags = Tag::orderBy('name')->withCount('cocktails')->filterByBar()->get();

        return TagResource::collection($tags);
    }

    public function show(Request $request, int $id): JsonResource
    {
        $tag = Tag::withCount('cocktails')->findOrFail($id);

        if ($request->user()->cannot('show', $tag)) {
            abort(403);
        }

        return new TagResource($tag);
    }

    public function store(TagRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', Tag::class)) {
            abort(403);
        }

        $tag = new Tag();
        $tag->name = $request->post('name');
        $tag->bar_id = bar()->id;
        $tag->save();

        return (new TagResource($tag))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('tags.show', $tag->id));
    }

    public function update(TagRequest $request, int $id): JsonResource
    {
        $tag = Tag::findOrFail($id);

        if ($request->user()->cannot('edit', $tag)) {
            abort(403);
        }

        $tag->name = $request->post('name');
        $tag->save();

        $cocktailIds = DB::table('cocktail_tag')->select('cocktail_id')->where('tag_id', $tag->id)->pluck('cocktail_id');
        Cocktail::find($cocktailIds)->each(fn ($cocktail) => $cocktail->searchable());

        return new TagResource($tag);
    }

    public function delete(Request $request, int $id): Response
    {
        $tag = Tag::findOrFail($id);

        if ($request->user()->cannot('delete', $tag)) {
            abort(403);
        }

        $cocktailIds = DB::table('cocktail_tag')->select('cocktail_id')->where('tag_id', $id)->pluck('cocktail_id');
        $tag->delete();
        Cocktail::find($cocktailIds)->each(fn ($cocktail) => $cocktail->searchable());

        return response(null, 204);
    }
}
