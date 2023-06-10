<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Scraper\Manager;
use Kami\Cocktail\Http\Requests\CocktailScrapeRequest;

class ScrapeController extends Controller
{
    public function cocktail(CocktailScrapeRequest $request): JsonResponse
    {
        $dataToImport = [];

        $url = $request->post('url');
        if ($url) {
            try {
                $scraper = Manager::scrape($url);
            } catch (Throwable $e) {
                abort(404, $e->getMessage());
            }

            $dataToImport = $scraper->toArray();
        }

        $json = $request->post('json');
        if ($json) {
            if (!is_array($json)) {
                $json = json_decode($json);
            }

            $dataToImport = $json;
        }

        return response()->json([
            'data' => [
                'result' => $dataToImport,
            ]
        ]);
    }
}
