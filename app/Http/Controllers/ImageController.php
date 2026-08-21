<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\Image;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\Cache;
use Kami\Cocktail\Http\Requests\ImageRequest;
use Kami\Cocktail\Http\Resources\ImageResource;
use Kami\Cocktail\Services\Image\ImageResolver;
use BarAssistant\Application\Image\ImageService;
use Illuminate\Http\Resources\Json\JsonResource;
use BarAssistant\Application\Image\DTO\CreateImage;
use Kami\Cocktail\Services\Image\ImageUploadService;
use Kami\Cocktail\Services\Image\ImageStorageService;
use Kami\Cocktail\Services\Image\ImageThumbnailService;
use BarAssistant\Application\Image\DTO\UpdateImageRequest;

class ImageController extends Controller
{
    #[OAT\Get(path: '/images/{id}', tags: ['Images'], operationId: 'showImage', description: 'Show a single image', summary: 'Show image', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(ImageResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function show(Request $request, int $id): JsonResource
    {
        $image = Image::findOrFail($id);

        if ($request->user()?->cannot('show', $image)) {
            abort(403);
        }

        return new ImageResource($image);
    }

    #[OAT\Post(path: '/images', tags: ['Images'], operationId: 'uploadImage', summary: 'Upload image', description: 'Used to upload multiple images at once. Uploaded images via this endpoint will not be attached to any resource. Images are converted to WebP format with 85% quality of the original image.', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\MediaType(mediaType: 'multipart/form-data', schema: new OAT\Schema(type: 'object', required: ['images'], properties: [
                new OAT\Property(property: 'images', type: 'array', items: new OAT\Items(ref: BAO\Schemas\ImageRequest::class)),
            ])),
        ]
    ))]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(ImageResource::class),
    ])]
    public function store(ImageUploadService $imageUploadService, ImageService $imageService, ImageResolver $imageResolver, ImageRequest $request): JsonResource
    {
        $imageIds = [];

        /** @var array{image?: \Illuminate\Http\UploadedFile|string, id?: int, sort?: int, copyright?: string}[] */
        $formImages = $request->images ?? [];
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        foreach ($formImages as $requestImage) {
            if (isset($requestImage['image'])) {
                $imageSource = $imageResolver->resolveImageSource($requestImage['image']);
            } else {
                $imageSource = null;
            }

            $uploadedImage = null;
            if ($imageSource !== null) {
                $uploadedImage = $imageUploadService->uploadImage($imageSource);
            }

            if (isset($requestImage['id'])) {
                $existingImage = Image::findOrFail($requestImage['id']);
                if ($user->cannot('edit', $existingImage)) {
                    continue;
                }

                Cache::forget('image_thumb_' . $requestImage['id']);

                if ($uploadedImage) {
                    $uploadedImage = $imageUploadService->changeImage((int) $requestImage['id'], $uploadedImage);
                }

                $imageResult = $imageService->updateImage(new UpdateImageRequest(
                    id: (int) $requestImage['id'],
                    imageFilePath: $uploadedImage?->path,
                    imageFileExtension: $uploadedImage?->extension,
                    userId: $user->id,
                    sort: (int) ($requestImage['sort'] ?? 0),
                    copyright: $requestImage['copyright'] ?? null,
                    placeholderHash: $uploadedImage?->placeholderHash,
                    disk: $uploadedImage ? 'uploads' : null,
                    storageOrigin: $uploadedImage ? 'owned' : null,
                ));
            } else {
                if ($uploadedImage === null) {
                    continue;
                }

                $imageResult = $imageService->createImage(new CreateImage(
                    imageFilePath: $uploadedImage->path,
                    imageFileExtension: $uploadedImage->extension,
                    userId: $user->id,
                    sort: (int) ($requestImage['sort'] ?? 0),
                    copyright: $requestImage['copyright'] ?? null,
                    placeholderHash: $uploadedImage->placeholderHash,
                ));
            }

            $imageIds[] = $imageResult->id;
        }

        return ImageResource::collection(Image::find($imageIds));
    }

    #[OAT\Delete(path: '/images/{id}', tags: ['Images'], operationId: 'deleteImage', description: 'Delete a specific image', summary: 'Delete image', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(Request $request, int $id): Response
    {
        $image = Image::findOrFail($id);

        if ($request->user()?->cannot('delete', $image)) {
            abort(403);
        }

        $image->delete();

        return new Response(null, 204);
    }

    #[OAT\Get(path: '/images/{id}/thumb', tags: ['Images'], operationId: 'getImageThumbnail', description: 'Generate a thumbnail of a specific image', summary: 'Get thumbnail', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], security: [])]
    #[BAO\SuccessfulResponse(content: [
        new OAT\MediaType(mediaType: 'image/jpg', schema: new OAT\Schema(type: 'string', format: 'binary')),
    ])]
    #[BAO\NotFoundResponse]
    public function thumb(int $id, ImageStorageService $imageStorage): Response
    {
        [$responseContent, $etag] = Cache::remember('image_thumb_' . $id, 1 * 24 * 60 * 60, function () use ($id, $imageStorage) {
            $dbImage = Image::findOrFail($id);

            $responseContent = $imageStorage->withTemporaryFile(
                image: $dbImage,
                callback: fn (string $path): string => ImageThumbnailService::generateThumbnail($path),
            );
            if ($dbImage->updated_at) {
                $etag = md5($dbImage->id . '-' . $dbImage->updated_at->format('Y-m-d H:i:s'));
            } else {
                $etag = md5($dbImage->id . '-' . $dbImage->created_at?->format('Y-m-d H:i:s'));
            }

            return [$responseContent, $etag];
        });

        $notModified = isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag;
        $statusCode = $notModified ? 304 : 200;

        return new Response($responseContent, $statusCode, [
            'Content-Type' => 'image/webp',
            'Content-Length' => strlen((string) $responseContent),
            'Etag' => $etag
        ]);
    }
}
