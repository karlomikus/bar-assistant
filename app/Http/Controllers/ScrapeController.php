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
        $url = $request->post('url');

        try {
            $scraper = Manager::scrape($url);
        } catch (Throwable $e) {
            abort(404, $e->getMessage());
        }

        $scrapedData = $scraper->toArray();

        return response()->json([
            'data' => [
                'result' => $scrapedData,
            ]
        ]);
    }
}
