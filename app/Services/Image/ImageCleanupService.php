<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image;

use Throwable;
use Kami\Cocktail\Models\Image;
use Illuminate\Container\Attributes\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Kami\Cocktail\Services\Image\DTO\ImageCleanupResult;

final readonly class ImageCleanupService
{
    public function __construct(
        #[Storage('uploads')]
        private Filesystem $filesystem,
    ) {
    }

    /**
     * Removes orphaned image records and leftover temporary upload files.
     */
    public function deleteUnusedImages(): ImageCleanupResult
    {
        $deletedImages = 0;
        $images = Image::whereNull('imageable_id')->get();

        foreach ($images as $image) {
            try {
                $image->delete();
                $deletedImages++;
            } catch (Throwable) {
            }
        }

        $tempFiles = $this->filesystem->files('temp/');

        if (count($tempFiles) > 0) {
            $this->filesystem->delete($tempFiles);
        }

        return new ImageCleanupResult(
            deletedImages: $deletedImages,
            deletedTemporaryFiles: count($tempFiles),
        );
    }
}
