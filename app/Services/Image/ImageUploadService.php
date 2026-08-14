<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image;

use Throwable;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Container\Attributes\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Kami\Cocktail\Services\Image\DTO\ImageUploadResult;

final readonly class ImageUploadService
{
    public function __construct(
        #[Storage('uploads')]
        private Filesystem $filesystem,
        private LoggerInterface $log,
    ) {
    }

    /**
     * Saves processed image buffer to a filesystem
     */
    public function uploadImage(string $imageBuffer): ?ImageUploadResult
    {
        $imageResult = null;
        $fileExtension = 'webp';
        $path = 'temp/' . Str::random(40) . '.' . $fileExtension;

        try {
            $vipsImage = ImageResizeService::resizeImageTo($imageBuffer);
            $thumbHash = ImageHashingService::generatePlaceholderHashFromBuffer($imageBuffer);
            $this->filesystem->put(
                $path,
                $vipsImage->writeToBuffer('.' . $fileExtension, ['Q' => 85])
            );

            $imageResult = new ImageUploadResult(
                path: $path,
                extension: $fileExtension,
                placeholderHash: $thumbHash,
            );
        } catch (Throwable $e) {
            $this->log->error('[IMAGE_UPLOAD_SERVICE] File upload error | ' . $e->getMessage());
        }

        return $imageResult;
    }

    /**
     * Change image file of existing image
     */
    public function changeImage(int $imageId, ImageUploadResult $newImage): ImageUploadResult
    {
        $imageModel = Image::findOrFail($imageId);

        // Delete old image file
        if ($this->filesystem->exists($imageModel->file_path)) {
            $this->filesystem->delete($imageModel->file_path);
        }

        // For temporary images we can just use the new image that is also temporary
        if ($imageModel->isTemporaryImage()) {
            return $newImage;
        }

        // For images with attached resource, we need to move it to correct
        // upload folder and then update the path
        $newImagePath = $imageModel->imageable->generateImagePath($newImage->extension);

        if ($this->filesystem->exists($newImage->path)) {
            $this->filesystem->move($newImage->path, $newImagePath);
        }

        return new ImageUploadResult(
            path: $newImagePath,
            extension: $newImage->extension,
            placeholderHash: $newImage->placeholderHash,
        );
    }

    public function cleanBarImages(int $barId): void
    {
        $bar = Bar::findOrFail($barId);
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

        $this->filesystem->deleteDirectory('cocktails/' . $bar->id . '/');
        $this->filesystem->deleteDirectory('ingredients/' . $bar->id . '/');
        if ($barLogoPath) {
            $this->filesystem->delete($barLogoPath);
        }
    }
}
