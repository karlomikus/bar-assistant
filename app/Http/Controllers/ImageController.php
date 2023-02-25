<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Image;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\Services\ImageService;
use Intervention\Image\ImageManagerStatic;
use Kami\Cocktail\Http\Requests\ImageRequest;
use Kami\Cocktail\Http\Resources\ImageResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\DataObjects\Image as ImageDTO;

class ImageController extends Controller
{
    public function show(int $id): JsonResource
    {
        $image = Image::findOrFail($id);

        return new ImageResource($image);
    }

    public function store(ImageService $imageservice, ImageRequest $request): JsonResource
    {
        $images = [];
        foreach ($request->images as $formImage) {
            try {
                $image = new ImageDTO(
                    null,
                    ImageManagerStatic::make($formImage['image']),
                    $formImage['copyright']
                );
            } catch (Throwable $e) {
                Log::error($e->getMessage());
                abort(500, 'Unable to create an image file!');
            }
            $images[] = $image;
        }

        $images = $imageservice->uploadAndSaveImages($images, $request->user()->id);

        return ImageResource::collection($images);
    }

    public function update(int $id, ImageService $imageservice, Request $request): JsonResource
    {
        $image = Image::findOrFail($id);

        if ($request->user()->cannot('edit', $image)) {
            abort(403);
        }

        $imageDTO = new ImageDTO(
            $image->id,
            null,
            $request->input('copyright')
        );

        $image = $imageservice->updateImage($imageDTO);

        return new ImageResource($image);
    }

    public function delete(Request $request, int $id): Response
    {
        $image = Image::findOrFail($id);

        if ($request->user()->cannot('delete', $image)) {
            abort(403);
        }

        $image->delete();

        return response(null, 204);
    }

    public function thumb(int $id)
    {
        [$content, $etag] = Cache::remember('image_thumb_' . $id, 1 * 24 * 60 * 60, function () use ($id) {
            $dbImage = Image::findOrFail($id);
            $disk = Storage::disk('bar-assistant');
            $responseContent = (string) ImageManagerStatic::make($disk->get($dbImage->file_path))->fit(400, 400)->encode();
            $etag = md5($dbImage->id . '-' . $dbImage->updated_at->format('Y-m-d H:i:s'));

            return [$responseContent, $etag];
        });

        $mime = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $content);
        $notModified = isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag;
        $statusCode = $notModified ? 304 : 200;

        return new Response($content, $statusCode, [
            'Content-Type' => $mime,
            'Content-Length' => strlen($content),
            'Etag' => $etag
        ]);
    }
}
