<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Kami\Cocktail\Models\Glass;
use Kami\Cocktail\Http\Resources\GlassResource;

class GlassController extends Controller
{
    public function index()
    {
        $glasses = Glass::orderBy('name')->get();

        return GlassResource::collection($glasses);
    }

    public function show(int $id)
    {
        $glass = Glass::findOrFail($id);

        return new GlassResource($glass);
    }
}
