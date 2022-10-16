<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function store(Request $request)
    {
        $files = $request->file('images');
        foreach ($files as $file) {
            // $file->storeAs('test', 'name' . rand() . '.' . $file->extension(), 'app_images');
        }
    }
}
