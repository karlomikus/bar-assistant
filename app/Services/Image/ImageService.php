<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image;

use Throwable;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Image;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\DTO\Image\Image as ImageDTO;
use Illuminate\Contracts\Filesystem\Factory as FileSystemFactory;

final readonly class ImageService
{
    public function __construct(
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
            if (!($dtoImage instanceof ImageDTO)) {
                continue;
            }

            if ($dtoImage->id) {
                $image = Image::findOrFail($dtoImage->id);

                if ($dtoImage->file) {
                    try {
                        [$filepath, $fileExtension] = $this->processImageFile($dtoImage->file);
                        $thumbHash = ImageHashingService::generatePlaceholderHashFromFilepath($this->filesystemManager->disk('uploads')->path($filepath));

                        $image->file_path = $filepath;
                        $image->placeholder_hash = $thumbHash;
                    } catch (Throwable) {
                        continue;
                    }
                }

                $image->copyright = $dtoImage->copyright;
                $image->sort = $dtoImage->sort;
                $image->updated_user_id = $userId;
                $image->updated_at = now();
                $image->save();
            } else {
                if (!$dtoImage->file) {
                    continue;
                }

                try {
                    [$filepath, $fileExtension] = $this->processImageFile($dtoImage->file);
                    $thumbHash = ImageHashingService::generatePlaceholderHashFromFilepath($this->filesystemManager->disk('uploads')->path($filepath));
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
            }

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
                [$filepath, $fileExtension] = $this->processImageFile($imageDTO->file);
                $thumbHash = ImageHashingService::generatePlaceholderHashFromFilepath($this->filesystemManager->disk('uploads')->path($filepath));

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

    /**
     * @return array<string>
     */
    private function processImageFile(string $image, ?string $filename = null): array
    {
        $filename = $filename ?? Str::random(40);

        try {
            $fileExtension = 'webp';
            $filepath = 'temp/' . $filename . '.' . $fileExtension;

            $vipsImage = ImageResizeService::resizeImageTo($image);
            $vipsImage->writeToFile($this->filesystemManager->disk('uploads')->path($filepath), ['Q' => 85]);
        } catch (Throwable $e) {
            $this->log->error('[IMAGE_SERVICE] ' . $e->getMessage());

            throw $e;
        }

        return [$filepath, $fileExtension];
    }
}
