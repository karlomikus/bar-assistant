<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Kami\Cocktail\Models\Image;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\Services\ImageService;
use Kami\Cocktail\Http\Requests\ImageRequest;
use Kami\Cocktail\Http\Resources\ErrorResource;
use Kami\Cocktail\Http\Resources\ImageResource;
use Kami\Cocktail\Http\Resources\SuccessActionResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ImageController extends Controller
{
    public function show(int $id)
    {
        try {
            $image = Image::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return (new ErrorResource($e))->response()->setStatusCode(404);
        } catch (Throwable $e) {
            return (new ErrorResource($e))->response()->setStatusCode(400);
        }

        return new ImageResource($image);
    }

    public function store(ImageService $imageservice, ImageRequest $request)
    {
        $images = $imageservice->uploadAndSaveImages($request->images);

        return ImageResource::collection($images);
    }

    public function delete(int $id)
    {
        $disk = Storage::disk('app_images');

        try {
            $image = Image::findOrFail($id);
            if ($disk->exists($image->file_path)) {
                $disk->delete($image->file_path);
            }
            $image->delete();
        } catch (ModelNotFoundException $e) {
            return (new ErrorResource($e))->response()->setStatusCode(404);
        } catch (Throwable $e) {
            return (new ErrorResource($e))->response()->setStatusCode(400);
        }

        return new SuccessActionResource((object) ['id' => $id]);
    }
}
