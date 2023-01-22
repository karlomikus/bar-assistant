<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Kami\Cocktail\Scraper\Manager;

class ScrapeController extends Controller
{
    public function cocktail(Request $request)
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
