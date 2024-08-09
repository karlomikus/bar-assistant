<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Export;
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

    #[OAT\Get(path: '/exports/{id}/Download', tags: ['Exports'], summary: 'Download export', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new OAT\MediaType(mediaType: 'application/octet-stream', example: 'binary'),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function download(Request $request, int $id): BinaryFileResponse
    {
        $export = Export::findOrFail($id);

        if ($request->user()->cannot('download', $export)) {
            abort(403);
        }

        return response()->download($export->getFullPath());
    }
}
