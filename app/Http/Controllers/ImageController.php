<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Image;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\Services\ImageService;
use Intervention\Image\ImageManagerStatic;
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

    public function thumb(int $id): Response
    {
        [$content, $etag] = Cache::remember('image_thumb_' . $id, 1 * 24 * 60 * 60, function () use ($id) {
            $dbImage = Image::findOrFail($id);
            $disk = Storage::disk('app_images');
            $responseContent = (string) ImageManagerStatic::make($disk->get($dbImage->file_path))->fit(200, 200)->encode();
            $etag = md5($dbImage->id . '-' . $dbImage->updated_at->format('Y-m-d H:i:s'));

            return [$responseContent, $etag];
        });

        $notModified = isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag;
        $statusCode = $notModified ? 304 : 200;

        return new Response($content, $statusCode, [
            'Etag' => $etag
        ]);
    }
}
