<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Scraper\Manager;
use Kami\Cocktail\Services\ImportService;
use Kami\Cocktail\Http\Requests\ImportRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\CocktailResource;
use Kami\Cocktail\Http\Resources\CollectionResource;

class ImportController extends Controller
{
    public function cocktail(ImportRequest $request, ImportService $importService): JsonResponse|JsonResource
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
                abort(400, $e->getMessage());
            }

            $dataToImport = $scraper->toArray();
        }

        if ($type === 'json') {
            if (!is_array($source)) {
                if (!$source = json_decode($source)) {
                    abort(400, 'Unable to parse the JSON string');
                }
            }

            $dataToImport = $source;
        }

        if ($type === 'yaml' || $type === 'yml') {
            try {
                $dataToImport = Yaml::parse($source);
            } catch (Throwable) {
                abort(400, sprintf('Unable to parse the YAML string'));
            }
        }

        if ($type === 'collection') {
            if (!is_array($source)) {
                if (!$source = json_decode($source, true)) {
                    abort(400, 'Unable to parse the JSON string');
                }
            }

            if (count($source['cocktails']) === 0) {
                abort(400, sprintf('No cocktails found'));
            }

            $collection = $importService->importCocktailCollection($source, $request->user()->id, bar()->id);

            return new CollectionResource($collection);
        }

        if ($save) {
            $dataToImport = new CocktailResource($importService->importCocktailFromArray($dataToImport, $request->user()->id, bar()->id));
        }

        return response()->json([
            'data' => $dataToImport
        ]);
    }
}
