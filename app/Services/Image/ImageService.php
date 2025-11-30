<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image;

use Throwable;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Image;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\OpenAPI\Schemas\ImageRequest;
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
     * @param array<ImageRequest> $requestImages
     * @param int $userId
     * @return array<Image>
     */
    public function uploadAndSaveImages(array $requestImages, int $userId): array
    {
        $images = [];
        foreach ($requestImages as $dtoImage) {
            if ($dtoImage->id) {
                $image = Image::findOrFail($dtoImage->id);

                if ($dtoImage->image) {
                    try {
                        [$filepath, $fileExtension, $thumbHash] = $this->processImageFile($dtoImage->image);

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
                if (!$dtoImage->image) {
                    continue;
                }

                try {
                    [$filepath, $fileExtension, $thumbHash] = $this->processImageFile($dtoImage->image);
                } catch (Throwable $e) {
                    $this->log->error('[IMAGE_SERVICE] File upload error | ' . $e->getMessage());
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

    public function updateImage(int $imageId, ImageRequest $imageDTO, int $userId): Image
    {
        $image = Image::findOrFail($imageId);

        if ($imageDTO->image) {
            $oldFilePath = $image->file_path;
            try {
                [$filepath, $fileExtension, $thumbHash] = $this->processImageFile($imageDTO->image);

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
        $barLogoPath = $bar->images->first()?->file_path;

        DB::transaction(function () use ($cocktailIds, $ingredientIds, $bar) {
            DB::table('images')
                ->where('imageable_type', \Kami\Cocktail\Models\Cocktail::class)
                ->whereIn('imageable_id', $cocktailIds)
                ->delete();

            DB::table('images')
                ->where('imageable_type', \Kami\Cocktail\Models\Ingredient::class)
                ->whereIn('imageable_id', $ingredientIds)
                ->delete();

            DB::table('images')
                ->where('imageable_type', \Kami\Cocktail\Models\Bar::class)
                ->where('imageable_id', $bar->id)
                ->delete();
        });

        $this->filesystemManager->disk('uploads')->deleteDirectory('cocktails/' . $bar->id . '/');
        $this->filesystemManager->disk('uploads')->deleteDirectory('ingredients/' . $bar->id . '/');
        if ($barLogoPath) {
            $this->filesystemManager->disk('uploads')->delete($barLogoPath);
        }
    }

    /**
     * @return array<string>
     */
    private function processImageFile(string $image, ?string $filename = null): array
    {
        $filename ??= Str::random(40);

        try {
            $fileExtension = 'webp';
            $filepath = 'temp/' . $filename . '.' . $fileExtension;

            $vipsImage = ImageResizeService::resizeImageTo($image);
            $thumbHash = ImageHashingService::generatePlaceholderHashFromBuffer($image);
            $this->filesystemManager->disk('uploads')->put(
                $filepath,
                $vipsImage->writeToBuffer('.' . $fileExtension, ['Q' => 85])
            );
        } catch (Throwable $e) {
            $this->log->error('[IMAGE_SERVICE] ' . $e->getMessage());

            throw $e;
        }

        return [$filepath, $fileExtension, $thumbHash];
    }
}
