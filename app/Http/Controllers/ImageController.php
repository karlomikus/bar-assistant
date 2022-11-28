<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Image;
use Kami\Cocktail\Services\ImageService;
use Kami\Cocktail\Http\Requests\ImageRequest;
use Kami\Cocktail\Http\Resources\ImageResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageController extends Controller
{
    public function show(int $id): JsonResource
    {
        $image = Image::findOrFail($id);

        return new ImageResource($image);
    }

    public function store(ImageService $imageservice, ImageRequest $request): JsonResource
    {
        $images = $imageservice->uploadAndSaveImages($request->images);

        return ImageResource::collection($images);
    }

    public function update(int $id, ImageService $imageservice, Request $request): JsonResource
    {
        $image = $imageservice->updateImage($id, null, $request->input('copyright'));

        return new ImageResource($image);
    }

    public function delete(int $id): Response
    {
        Image::findOrFail($id)->delete();

        return response(null, 204);
    }
}
