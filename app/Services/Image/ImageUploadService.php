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
}
