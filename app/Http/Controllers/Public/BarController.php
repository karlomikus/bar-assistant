<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers\Public;

use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Http\Controllers\Controller;
use Kami\Cocktail\Http\Resources\Public\BarResource;

class BarController extends Controller
{
    public function show(int $barId): BarResource
    {
        $bar = Bar::findOrFail($barId);
        if (!$bar->is_public) {
            abort(404);
        }

        return new BarResource($bar);
    }
}
