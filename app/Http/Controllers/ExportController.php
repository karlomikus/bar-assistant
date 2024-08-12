<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Bar;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\Export;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\FileToken;
use Kami\Cocktail\Jobs\StartRecipesExport;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\ExportResource;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    #[OAT\Get(path: '/exports', tags: ['Exports'], summary: 'Show a list of exports')]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapItemsWithData(BAO\Schemas\Export::class),
    ])]
    public function index(Request $request): JsonResource
    {
        $exports = Export::orderBy('created_at', 'desc')
            ->where('created_user_id', $request->user()->id)
            ->get();

        return ExportResource::collection($exports);
    }

    #[OAT\Post(path: '/exports', tags: ['Exports'], summary: 'Create a new export', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(property: 'type', type: 'string', example: 'json'),
                new OAT\Property(property: 'bar_id', type: 'integer', example: 1),
            ]),
        ]
    ))]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Export::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function store(Request $request): ExportResource
    {
        $type = $request->post('type', 'json');
        $bar = Bar::findOrFail($request->post('bar_id'));

        if ($request->user()->cannot('createExport', $bar)) {
            abort(403);
        }

        $export = new Export();
        $export->withFilename();
        $export->bar_id = $bar->id;
        $export->is_done = false;
        $export->created_user_id = $request->user()->id;
        $export->save();

        StartRecipesExport::dispatch($bar->id, $type, $export);

        return new ExportResource($export);
    }

    #[OAT\Delete(path: '/exports/{id}', tags: ['Exports'], summary: 'Delete export', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(Request $request, int $id): Response
    {
        $export = Export::findOrFail($id);

        if ($request->user()->cannot('delete', $export)) {
            abort(403);
        }

        $export->delete();

        return new Response(null, 204);
    }

    #[OAT\Get(path: '/exports/{id}/download', tags: ['Exports'], summary: 'Download export', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new OAT\Parameter(name: 't', in: 'query', description: 'Token', required: true),
        new OAT\Parameter(name: 'e', in: 'query', description: 'Timestamp', required: true),
    ], security: [])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new OAT\MediaType(mediaType: 'application/octet-stream', example: 'binary'),
    ])]
    #[BAO\NotFoundResponse]
    public function download(Request $request, int $id): BinaryFileResponse
    {
        $export = Export::findOrFail($id);

        if (!FileToken::check(
            $request->get('t'),
            $id,
            $export->filename,
            DateTimeImmutable::createFromFormat('U', $request->get('e'))
        )) {
            abort(404);
        }

        return response()->download($export->getFullPath());
    }

    #[OAT\Post(path: '/exports/{id}/download', tags: ['Exports'], summary: 'Generate download link', description: 'Generates a publicly accessible download link for the export. The link will be valid for 1 minute by default.', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\FileDownloadLink::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function generateDownloadLink(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $export = Export::findOrFail($id);

        if ($request->user()->cannot('download', $export)) {
            abort(403);
        }

        if ($export->is_done === false) {
            abort(400, 'Export still in progress');
        }

        $expires = new DateTimeImmutable('+1 hour');
        $token = FileToken::generate($export->id, $export->filename, $expires);

        return response()->json([
            'data' => [
                'url' => route('exports.download', ['id' => $export->id, 't' => $token, 'e' => $expires->getTimestamp()]),
                'token' => $token,
                'expires' => $expires->format(DateTimeImmutable::ATOM),
            ]
        ]);
    }
}
