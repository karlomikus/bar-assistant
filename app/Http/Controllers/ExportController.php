<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Export;
use Kami\Cocktail\Jobs\StartRecipesExport;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\ExportResource;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function index(Request $request): JsonResource
    {
        $exports = Export::orderBy('created_at', 'desc')
            ->where('created_user_id', $request->user()->id)
            ->get();

        return ExportResource::collection($exports);
    }

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

    public function delete(Request $request, int $id): Response
    {
        $export = Export::findOrFail($id);

        if ($request->user()->cannot('delete', $export)) {
            abort(403);
        }

        $export->delete();

        return new Response(null, 204);
    }

    public function download(Request $request, int $id): BinaryFileResponse
    {
        $export = Export::findOrFail($id);

        if ($request->user()->cannot('download', $export)) {
            abort(403);
        }

        return response()->download($export->getFullPath());
    }
}
