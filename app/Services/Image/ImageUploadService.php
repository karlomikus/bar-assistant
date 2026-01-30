<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image;

use Throwable;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
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
        try {
            $imageResult = $this->processImagePipeline($imageBuffer);
        } catch (Throwable $e) {
            $this->log->error('[IMAGE_UPLOAD_SERVICE] File upload error | ' . $e->getMessage());
        }

        return $imageResult;
    }

    private function processImagePipeline(string $image, ?string $filename = null): ImageUploadResult
    {
        $filename ??= Str::random(40);

        try {
            $fileExtension = 'webp';
            $filepath = 'temp/' . $filename . '.' . $fileExtension;

            $vipsImage = ImageResizeService::resizeImageTo($image);
            $thumbHash = ImageHashingService::generatePlaceholderHashFromBuffer($image);
            $this->filesystem->put(
                $filepath,
                $vipsImage->writeToBuffer('.' . $fileExtension, ['Q' => 85])
            );
        } catch (Throwable $e) {
            $this->log->error('[IMAGE_SERVICE] ' . $e->getMessage());

            throw $e;
        }

        return new ImageUploadResult(
            path: $filepath,
            extension: $fileExtension,
            placeholderHash: $thumbHash,
        );
    }
}
