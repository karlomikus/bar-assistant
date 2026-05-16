<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\Image;
use Illuminate\Http\UploadedFile;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Kami\Cocktail\Http\Resources\ImageResource;
use Symfony\Component\HttpFoundation\File\File;
use BarAssistant\Application\Image\ImageService;
use Illuminate\Http\Resources\Json\JsonResource;
use BarAssistant\Application\Image\DTO\CreateImage;
use Kami\Cocktail\Services\Image\ImageUploadService;
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

        if ($request->user()->cannot('show', $image)) {
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
    public function store(ImageUploadService $imageUploadService, ImageService $imageService, Request $request): JsonResource
    {
        $imageIds = [];
        foreach ($request->images ?? [] as $requestImage) {
            $imageSource = $this->getValidImageSource($requestImage);
            $uploadedImage = null;
            if ($imageSource !== null) {
                $uploadedImage = $imageUploadService->uploadImage($imageSource);
            }

            if (isset($requestImage['id'])) {
                if ($uploadedImage) {
                    $uploadedImage = $imageUploadService->changeImage((int) $requestImage['id'], $uploadedImage);
                }

                $imageResult = $imageService->updateImage(new UpdateImageRequest(
                    id: (int) $requestImage['id'],
                    imageFilePath: $uploadedImage?->path,
                    imageFileExtension: $uploadedImage?->extension,
                    userId: $request->user()->id,
                    sort: (int) ($requestImage['sort'] ?? 0),
                    copyright: $requestImage['copyright'] ?? null,
                    placeholderHash: $uploadedImage?->placeholderHash,
                ));
            } else {
                if ($uploadedImage === null) {
                    continue;
                }

                $imageResult = $imageService->createImage(new CreateImage(
                    imageFilePath: $uploadedImage->path,
                    imageFileExtension: $uploadedImage->extension,
                    userId: $request->user()->id,
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

        if ($request->user()->cannot('delete', $image)) {
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
    public function thumb(int $id): Response
    {
        [$responseContent, $etag] = Cache::remember('image_thumb_' . $id, 1 * 24 * 60 * 60, function () use ($id) {
            $dbImage = Image::findOrFail($id);

            $responseContent = ImageThumbnailService::generateThumbnail($dbImage->getPath());
            if ($dbImage->updated_at) {
                $etag = md5($dbImage->id . '-' . $dbImage->updated_at->format('Y-m-d H:i:s'));
            } else {
                $etag = md5($dbImage->id . '-' . $dbImage->created_at->format('Y-m-d H:i:s'));
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

    /**
     * @param array{image?: string|UploadedFile} $formImage
     */
    private function getValidImageSource(array $formImage): ?string
    {
        $imageSource = null;

        $imageFileRules = ['image' => 'image|max:51200'];

        if (isset($formImage['image']) && $formImage['image'] instanceof UploadedFile) {
            Validator::make($formImage, $imageFileRules)->validate();

            if ($sourceData = $formImage['image']->get()) {
                $imageSource = $sourceData;
            }
        }

        if (isset($formImage['image']) && is_string($formImage['image'])) {
            $tempFileObject = null;
            try {
                if ($imageSource = file_get_contents($formImage['image'])) {
                    $tempFileName = tempnam(sys_get_temp_dir(), 'bass');
                    file_put_contents($tempFileName, $imageSource);
                    $tempFileObject = new File($tempFileName);
                } else {
                    $imageSource = null;
                }
            } catch (Throwable) {
            }

            Validator::make(['image' => $tempFileObject], $imageFileRules)->validate();
        }

        return $imageSource;
    }
}
