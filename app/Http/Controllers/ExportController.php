<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Export;
use Kami\Cocktail\Jobs\StartRecipesExport;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\ExportResource;

class ExportController extends Controller
{
    public function index(Request $request): JsonResource
    {
        $exports = Export::orderBy('created_at')->where('created_user_id', $request->user()->id)->get();

        return ExportResource::collection($exports);
    }

    public function store(Request $request, int|string $barId): ExportResource
    {
        $type = $request->query('type', 'json');
        $bar = Bar::findOrFail($barId);

        $export = new Export();
        $export->withFilename();
        $export->bar_id = $bar->id;
        $export->is_done = false;
        $export->created_user_id = $request->user()->id;
        $export->save();

        StartRecipesExport::dispatch($bar->id, $type, $export);

        return new ExportResource($export);
    }
}
