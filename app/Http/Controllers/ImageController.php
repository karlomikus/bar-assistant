<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Kami\Cocktail\Models\Image;

class ImageController extends Controller
{
    public function show(int $id)
    {
        $image = Image::find($id);

        return $image;
    }
}
