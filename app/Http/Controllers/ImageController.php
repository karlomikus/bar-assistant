<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Kami\Cocktail\Models\Image;
use Kami\Cocktail\Services\ImageService;
use Kami\Cocktail\Http\Requests\ImageRequest;
use Kami\Cocktail\Http\Resources\ImageResource;
use Kami\Cocktail\Http\Resources\SuccessActionResource;

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

    public function delete(int $id)
    {
        Image::findOrFail($id)->delete();

        return new SuccessActionResource((object) ['id' => $id]);
    }
}
