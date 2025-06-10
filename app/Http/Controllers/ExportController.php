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
use Kami\Cocktail\Jobs\StartTypedExport;
use Kami\Cocktail\External\ExportTypeEnum;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\External\ForceUnitConvertEnum;
use Kami\Cocktail\Http\Resources\ExportResource;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    #[OAT\Get(path: '/exports', tags: ['Exports'], operationId: 'listExports', description: 'Show a list of all generated exports in a bar', summary: 'List exports')]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(ExportResource::class),
    ])]
    public function index(Request $request): JsonResource
    {
        $exports = Export::orderBy('created_at', 'desc')
            ->where('created_user_id', $request->user()->id)
            ->with('bar')
            ->get();

        return ExportResource::collection($exports);
    }

    #[OAT\Post(path: '/exports', tags: ['Exports'], operationId: 'saveExport', description: 'Start a new export process', summary: 'Create export', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\ExportRequest::class),
        ]
    ))]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(ExportResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\RateLimitResponse]
    public function store(Request $request): ExportResource
    {
        $bar = Bar::findOrFail((int) $request->post('bar_id'));

        if ($request->user()->cannot('createExport', $bar)) {
            abort(403);
        }

        $type = ExportTypeEnum::tryFrom($request->input('type', 'schema'));
        $units = ForceUnitConvertEnum::tryFrom($request->input('units', 'none'));

        $export = new Export();
        $export->bar_id = $bar->id;
        $export->filename = Export::generateFilename($type->getFilenameContext());
        $export->is_done = false;
        $export->created_user_id = $request->user()->id;
        $export->save();

        StartTypedExport::dispatch($bar->id, $type, $export, $units);

        $export->refresh();

        return new ExportResource($export);
    }

    #[OAT\Delete(path: '/exports/{id}', tags: ['Exports'], operationId: 'deleteExport', description: 'Delete a specific export', summary: 'Delete export', parameters: [
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

    #[OAT\Get(path: '/exports/{id}/download', tags: ['Exports'], operationId: 'downloadExport', description: 'Download a specific export', summary: 'Download export', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new OAT\Parameter(name: 't', in: 'query', description: 'Token', required: true, schema: new OAT\Schema(type: 'string')),
        new OAT\Parameter(name: 'e', in: 'query', description: 'Timestamp', required: true, schema: new OAT\Schema(type: 'string')),
    ], security: [])]
    #[BAO\SuccessfulResponse(content: [
        new OAT\MediaType(mediaType: 'application/octet-stream', example: 'binary'),
    ])]
    #[BAO\NotFoundResponse]
    public function download(Request $request, int $id): BinaryFileResponse
    {
        $export = Export::findOrFail($id);
        $date = DateTimeImmutable::createFromFormat('U', $request->get('e'));
        if ($date === false) {
            abort(404);
        }

        if (!FileToken::check($request->get('t'), $id, $export->filename, $date)) {
            abort(404);
        }

        return response()->download($export->getFullPath());
    }

    #[OAT\Post(path: '/exports/{id}/download', tags: ['Exports'], operationId: 'generateExportDownloadLink', summary: 'Generate link', description: 'Generates a publicly accessible download link for the export. The link will be valid for 1 minute by default.', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
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
                'url' => route('exports.download', ['id' => $export->id, 't' => $token, 'e' => $expires->getTimestamp()], false),
                'token' => $token,
                'expires' => $expires->format(DateTimeImmutable::ATOM),
            ]
        ]);
    }
}
