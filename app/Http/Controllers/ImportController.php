<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Scraper\Manager;
use Kami\Cocktail\Services\ImportService;
use Kami\Cocktail\Http\Requests\ImportRequest;
use Kami\Cocktail\Http\Resources\CocktailResource;

class ImportController extends Controller
{
    public function cocktail(ImportRequest $request, ImportService $importService): JsonResponse
    {
        $dataToImport = [];
        $type = $request->get('type', 'url');
        $save = $request->get('save', false);
        $source = $request->post('source');

        if ($type === 'url') {
            $request->validate(['source' => 'url']);
            try {
                $scraper = Manager::scrape($source);
            } catch (Throwable $e) {
                abort(404, $e->getMessage());
            }

            $dataToImport = $scraper->toArray();
        }

        if ($type === 'json') {
            if (!is_array($source)) {
                $source = json_decode($source);
            }

            $dataToImport = $source;
        }

        if ($save) {
            $dataToImport = new CocktailResource($importService->importCocktailFromArray($dataToImport));
        }

        return response()->json([
            'data' => $dataToImport
        ]);
    }
}
