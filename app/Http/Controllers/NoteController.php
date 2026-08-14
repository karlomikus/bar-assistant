<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Note;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Http\Requests\NoteRequest;
use BarAssistant\Application\Note\NoteService;
use Kami\Cocktail\Http\Resources\NoteResource;
use Kami\Cocktail\Http\Filters\NoteQueryFilter;
use Illuminate\Http\Resources\Json\JsonResource;
use BarAssistant\Application\Note\DTO\CreateNoteRequest;

class NoteController extends Controller
{
    public function __construct(
        private readonly NoteService $noteService,
    ) {
    }

    #[OAT\Get(path: '/notes', tags: ['Notes'], operationId: 'listNotes', description: 'Show list of all user notes', summary: 'List notes', parameters: [
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\PaginateData(NoteResource::class),
    ])]
    public function index(Request $request): JsonResource
    {
        $notes = (new NoteQueryFilter())->paginate($request->query('per_page', 100));

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
    public function store(NoteRequest $request): Response
    {
        $resourceId = $request->input('resource_id');
        $resourceType = $request->input('resource');

        if ($resourceType === null) {
            abort(404);
        }

        $resourceModel = match ($resourceType) {
            'cocktail' => Cocktail::findOrFail((int) $resourceId),
            default => throw new \InvalidArgumentException('Invalid resource type'),
        };

        if ($request->user()->cannot('addNote', $resourceModel)) {
            abort(403);
        }

        $noteResult = $this->noteService->createNote(new CreateNoteRequest(
            userId: $request->user()->id,
            resourceId: (int) $resourceId,
            resource: $resourceType,
            note: $request->input('note'),
        ));

        return new Response(status: 201, headers: ['Location' => route('notes.show', $noteResult->id, false)]);
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

        $this->noteService->deleteNote($id);

        return new Response(null, 204);
    }
}
