<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Throwable;
use Thumbhash\Thumbhash;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Kami\Cocktail\Models\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use Kami\Cocktail\DataObjects\Image as ImageDTO;
use Intervention\Image\Image as InterventionImage;
use Kami\Cocktail\Exceptions\ImageUploadException;
use function Thumbhash\extract_size_and_pixels_with_imagick;

class ImageService
{
    protected FilesystemAdapter $disk;

    public function __construct(
        private readonly LogManager $log,
    ) {
        $this->disk = Storage::disk('bar-assistant');
    }

    /**
     * Uploads and saves an image with filepath
     *
     * @param array<ImageDTO> $requestImages
     * @param int $userId
     * @return array<\Kami\Cocktail\Models\Image>
     * @throws ImageUploadException
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
            $image->user_id = $userId;
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
    public function updateImage(int $imageId, ImageDTO $imageDTO): Image
    {
        $image = Image::findOrFail($imageId);

        if ($imageDTO->file) {
            $oldFilePath = $image->file_path;
            try {
                [$thumbHash, $filepath, $fileExtension] = $this->processImageFile($imageDTO->file);

                $image->file_path = $filepath;
                $image->placeholder_hash = $thumbHash;
                $image->file_extension = $fileExtension;
            } catch (Throwable $e) {
                $this->log->error('[IMAGE_SERVICE] File upload error | ' . $e->getMessage());
            }

            try {
                $this->disk->delete($oldFilePath);
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

    /**
     * Generates ThumbHash key
     * @see https://evanw.github.io/thumbhash/
     *
     * @param InterventionImage $image
     * @param bool $destroyInstance Used for memory management
     * @return string
     */
    public function generateThumbHash(InterventionImage $image, bool $destroyInstance = false): string
    {
        if ($destroyInstance) {
            $content = $image->fit(100)->encode(null, 20);
            $image->destroy();
        } else {
            $image->backup();
            $content = $image->fit(100)->encode(null, 20);
            $image->reset();
        }

        [$width, $height, $pixels] = extract_size_and_pixels_with_imagick($content);
        $hash = Thumbhash::RGBAToHash($width, $height, $pixels);
        $key = Thumbhash::convertHashToString($hash);

        return $key;
    }

    private function processImageFile(InterventionImage $image, ?string $filename = null): array
    {
        $filename = $filename ?? Str::random(40);

        $fileExtension = match ($image->mime()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg'
        };

        $filepath = 'temp/' . $filename . '.' . $fileExtension;

        $thumbHash = null;
        try {
            $thumbHash = $this->generateThumbHash($image);
        } catch (Throwable $e) {
            $this->log->error('[IMAGE_SERVICE] ThumbHash Error | ' . $e->getMessage());

            throw $e;
        }

        try {
            $this->disk->put($filepath, (string) $image->encode());
        } catch (Throwable $e) {
            $this->log->error('[IMAGE_SERVICE] ' . $e->getMessage());

            throw $e;
        }

        return [$thumbHash, $filepath, $fileExtension];
    }
}
