<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Note;
use OpenApi\Attributes as OAT;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Http\Requests\NoteRequest;
use Kami\Cocktail\Http\Resources\NoteResource;
use Kami\Cocktail\Http\Filters\NoteQueryFilter;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteController extends Controller
{
    #[OAT\Get(path: '/notes', tags: ['Notes'], operationId: 'listNotes', description: 'Show list of all user notes', summary: 'List notes', parameters: [
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\PaginateData(NoteResource::class),
    ])]
    public function index(Request $request): JsonResource
    {
        $notes = (new NoteQueryFilter())->paginate($request->get('per_page', 100));

        return NoteResource::collection($notes->withQueryString());
    }

    #[OAT\Get(path: '/notes/{id}', tags: ['Notes'], operationId: 'showNote', description: 'Show a single note', summary: 'Show note', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(NoteResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function show(Request $request, int $id): JsonResource
    {
        $note = Note::findOrFail($id);

        if ($request->user()->cannot('show', $note)) {
            abort(403);
        }

        return new NoteResource($note);
    }

    #[OAT\Post(path: '/notes', tags: ['Notes'], operationId: 'saveNote', description: 'Create a new note', summary: 'Create note', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\NoteRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(NoteResource::class),
    ], headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function store(NoteRequest $request): JsonResponse
    {
        $resourceId = $request->input('resource_id');
        $resourceType = $request->input('resource');

        $resourceModel = match ($resourceType) {
            'cocktail' => Cocktail::findOrFail((int) $resourceId),
            default => abort(404)
        };

        if ($request->user()->cannot('addNote', $resourceModel)) {
            abort(403);
        }

        $note = $resourceModel->addNote($request->input('note'), $request->user()->id);

        return (new NoteResource($note))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('notes.show', $note->id));
    }

    #[OAT\Delete(path: '/notes/{id}', tags: ['Notes'], operationId: 'deleteNote', description: 'Delete a single note', summary: 'Delete note', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(Request $request, int $id): Response
    {
        $note = Note::findOrFail($id);

        if ($request->user()->cannot('delete', $note)) {
            abort(403);
        }

        $note->delete();

        return new Response(null, 204);
    }
}
