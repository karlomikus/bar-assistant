<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Kami\Cocktail\Http\Requests\ImageRequest;
use Kami\Cocktail\Http\Resources\ImageResource;
use Kami\Cocktail\Http\Resources\SuccessActionResource;
use Kami\Cocktail\Models\Image;
use Kami\Cocktail\Services\ImageService;

class ImageController extends Controller
{
    public function show(int $id)
    {
        $image = Image::findOrFail($id);

        return new ImageResource($image);
    }

    public function store(ImageService $imageservice, ImageRequest $request)
    {
        $images = $imageservice->uploadAndSaveImages($request->images);

        return ImageResource::collection($images);
    }

    public function update(int $id, ImageService $imageservice, Request $request)
    {
        $image = $imageservice->updateImage($id, null, $request->input('copyright'));

        return new ImageResource($image);
    }

    public function delete(int $id)
    {
        Image::findOrFail($id)->delete();

        return response(null, 204);
    }
}
