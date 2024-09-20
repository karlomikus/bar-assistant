<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Tag;
use OpenApi\Attributes as OAT;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Http\Requests\TagRequest;
use Kami\Cocktail\Http\Resources\TagResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TagController extends Controller
{
    #[OAT\Get(path: '/tags', tags: ['Tag'], summary: 'Show a list of all tags', parameters: [
        new BAO\Parameters\BarIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapItemsWithData(BAO\Schemas\Tag::class),
    ])]
    public function index(): JsonResource
    {
        $tags = Tag::orderBy('name')->withCount('cocktails')->filterByBar()->get();

        return TagResource::collection($tags);
    }

    #[OAT\Get(path: '/tags/{id}', tags: ['Tag'], summary: 'Show a single tag', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Tag::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function show(Request $request, int $id): JsonResource
    {
        $tag = Tag::withCount('cocktails')->findOrFail($id);

        if ($request->user()->cannot('show', $tag)) {
            abort(403);
        }

        return new TagResource($tag);
    }

    #[OAT\Post(path: '/tags', tags: ['Tag'], summary: 'Create a new tag', parameters: [
        new BAO\Parameters\BarIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\TagRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Tag::class),
    ], headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function store(TagRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', Tag::class)) {
            abort(403);
        }

        $tag = new Tag();
        $tag->name = $request->input('name');
        $tag->bar_id = bar()->id;
        $tag->save();

        return (new TagResource($tag))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('tags.show', $tag->id));
    }

    #[OAT\Put(path: '/tags/{id}', tags: ['Tag'], summary: 'Update tag', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\TagRequest::class),
        ]
    ))]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Tag::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function update(TagRequest $request, int $id): JsonResource
    {
        $tag = Tag::findOrFail($id);

        if ($request->user()->cannot('edit', $tag)) {
            abort(403);
        }

        $tag->name = $request->input('name');
        $tag->save();

        $cocktailIds = DB::table('cocktail_tag')->select('cocktail_id')->where('tag_id', $tag->id)->pluck('cocktail_id');
        Cocktail::find($cocktailIds)->each(fn ($cocktail) => $cocktail->searchable());

        return new TagResource($tag);
    }

    #[OAT\Delete(path: '/tags/{id}', tags: ['Tag'], summary: 'Delete tag', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(Request $request, int $id): Response
    {
        $tag = Tag::findOrFail($id);

        if ($request->user()->cannot('delete', $tag)) {
            abort(403);
        }

        $cocktailIds = DB::table('cocktail_tag')->select('cocktail_id')->where('tag_id', $id)->pluck('cocktail_id');
        $tag->delete();
        Cocktail::find($cocktailIds)->each(fn ($cocktail) => $cocktail->searchable());

        return new Response(null, 204);
    }
}
