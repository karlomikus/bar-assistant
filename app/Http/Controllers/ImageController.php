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
use Kami\Cocktail\Http\Requests\ImageRequest;
use Kami\Cocktail\Http\Resources\ImageResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\DataObjects\Image as ImageDTO;
use Kami\Cocktail\Http\Requests\ImageUpdateRequest;
use Intervention\Image\Facades\Image as ImageProcessor;

class ImageController extends Controller
{
    public function show(Request $request, int $id): JsonResource
    {
        $image = Image::findOrFail($id);

        if ($request->user()->cannot('show', $image)) {
            abort(403);
        }

        return new ImageResource($image);
    }

    public function store(ImageService $imageservice, ImageRequest $request): JsonResource
    {
        $images = [];
        foreach ($request->images as $formImage) {
            if (isset($formImage['image'])) {
                $imageSource = $formImage['image'];
            } else {
                $imageSource = $formImage['image_url'];
            }

            try {
                $image = new ImageDTO(
                    ImageProcessor::make($imageSource),
                    $formImage['copyright'],
                    (int) $formImage['sort'],
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

    public function update(int $id, ImageService $imageservice, ImageUpdateRequest $request): JsonResource
    {
        $image = Image::findOrFail($id);

        if ($request->user()->cannot('edit', $image)) {
            abort(403);
        }

        $imageDTO = new ImageDTO(
            $request->hasFile('image') ? ImageProcessor::make($request->file('image')) : null,
            $request->has('copyright') ? $request->input('copyright') : null,
            $request->has('sort') ? (int) $request->input('sort') : null,
        );

        $image = $imageservice->updateImage($id, $imageDTO, $request->user()->id);

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

    public function thumb(int $id): Response
    {
        $disk = Storage::disk('uploads');

        [$content, $etag] = Cache::remember('image_thumb_' . $id, 1 * 24 * 60 * 60, function () use ($id, $disk) {
            $dbImage = Image::findOrFail($id);
            $responseContent = (string) ImageProcessor::make($disk->get($dbImage->file_path))->fit(400, 400)->encode();
            if ($dbImage->updated_at) {
                $etag = md5($dbImage->id . '-' . $dbImage->updated_at->format('Y-m-d H:i:s'));
            } else {
                $etag = md5($dbImage->id . '-' . $dbImage->created_at->format('Y-m-d H:i:s'));
            }

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
