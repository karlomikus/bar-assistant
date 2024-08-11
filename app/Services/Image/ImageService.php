<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image;

use Throwable;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Image;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Encoders\WebpEncoder;
use Kami\Cocktail\DTO\Image\Image as ImageDTO;
use Intervention\Image\Interfaces\ImageInterface;
use Illuminate\Contracts\Filesystem\Factory as FileSystemFactory;

final readonly class ImageService
{
    public function __construct(
        private ImageHashingService $imageHashingService,
        private FileSystemFactory $filesystemManager,
        private LoggerInterface $log,
    ) {
    }

    /**
     * Uploads and saves an image with filepath
     *
     * @param array<ImageDTO> $requestImages
     * @param int $userId
     * @return array<\Kami\Cocktail\Models\Image>
     */
    public function uploadAndSaveImages(array $requestImages, int $userId): array
    {
        $images = [];
        foreach ($requestImages as $dtoImage) {
            if (!($dtoImage instanceof ImageDTO) || $dtoImage->file === null) {
                continue;
            }

            try {
                [$thumbHash, $filepath, $fileExtension] = $this->processImageFile($dtoImage->file);
            } catch (Throwable) {
                continue;
            }

            $image = new Image();
            $image->copyright = $dtoImage->copyright;
            $image->file_path = $filepath;
            $image->file_extension = $fileExtension;
            $image->created_user_id = $userId;
            $image->sort = $dtoImage->sort;
            $image->placeholder_hash = $thumbHash;
            $image->save();

            $images[] = $image;
        }

        return $images;
    }

    /**
     * Update image by id
     *
     * @param int $imageId
     * @param ImageDTO $imageDTO Image object
     * @return \Kami\Cocktail\Models\Image Database image model
     */
    public function updateImage(int $imageId, ImageDTO $imageDTO, int $userId): Image
    {
        $image = Image::findOrFail($imageId);

        if ($imageDTO->file) {
            $oldFilePath = $image->file_path;
            try {
                [$thumbHash, $filepath, $fileExtension] = $this->processImageFile($imageDTO->file);

                $image->file_path = $filepath;
                $image->placeholder_hash = $thumbHash;
                $image->file_extension = $fileExtension;
                $image->updated_user_id = $userId;
                $image->updated_at = now();
            } catch (Throwable $e) {
                $this->log->error('[IMAGE_SERVICE] File upload error | ' . $e->getMessage());
            }

            try {
                $this->filesystemManager->disk('uploads')->delete($oldFilePath);
            } catch (Throwable $e) {
                $this->log->error('[IMAGE_SERVICE] File delete error | ' . $e->getMessage());
            }
        }

        if ($imageDTO->copyright) {
            $image->copyright = $imageDTO->copyright;
        }

        if ($imageDTO->sort) {
            $image->sort = $imageDTO->sort;
        }

        $image->save();

        return $image;
    }

    public function cleanBarImages(Bar $bar): void
    {
        $cocktailIds = $bar->cocktails()->pluck('id');
        $ingredientIds = $bar->ingredients()->pluck('id');

        DB::transaction(function () use ($cocktailIds, $ingredientIds) {
            DB::table('images')
                ->where('imageable_type', \Kami\Cocktail\Models\Cocktail::class)
                ->whereIn('imageable_id', $cocktailIds)
                ->delete();

            DB::table('images')
                ->where('imageable_type', \Kami\Cocktail\Models\Ingredient::class)
                ->whereIn('imageable_id', $ingredientIds)
                ->delete();
        });

        $this->filesystemManager->disk('uploads')->deleteDirectory('cocktails/' . $bar->id . '/');
        $this->filesystemManager->disk('uploads')->deleteDirectory('ingredients/' . $bar->id . '/');
    }

    private function processImageFile(ImageInterface $image, ?string $filename = null): array
    {
        $filename = $filename ?? Str::random(40);

        $thumbHash = null;
        try {
            $thumbHashImage = clone $image;
            $thumbHash = $this->imageHashingService->generatePlaceholderHash($thumbHashImage);
            unset($thumbHashImage);
        } catch (Throwable $e) {
            $this->log->error('[IMAGE_SERVICE] ThumbHash Error | ' . $e->getMessage());

            throw $e;
        }

        try {
            $image->scaleDown(height: 1400);
            $encodedImage = $image->encode(new WebpEncoder(quality: 85));

            $fileExtension = 'webp';

            $filepath = 'temp/' . $filename . '.' . $fileExtension;

            $this->filesystemManager->disk('uploads')->put($filepath, $encodedImage->toString());
        } catch (Throwable $e) {
            $this->log->error('[IMAGE_SERVICE] ' . $e->getMessage());

            throw $e;
        }

        return [$thumbHash, $filepath, $fileExtension];
    }
}
