<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Throwable;
use Thumbhash\Thumbhash;
use Illuminate\Support\Str;
use Kami\Cocktail\Models\Bar;
use Illuminate\Log\LogManager;
use Kami\Cocktail\Models\Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\DTO\Image as ImageDTO;
use Illuminate\Filesystem\FilesystemAdapter;
use Intervention\Image\Encoders\AutoEncoder;
use Intervention\Image\Interfaces\ImageInterface;

use function Thumbhash\extract_size_and_pixels_with_imagick;

final class ImageService
{
    protected FilesystemAdapter $disk;

    public function __construct(
        private readonly LogManager $log,
    ) {
        $this->disk = Storage::disk('uploads');
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
     * @param ImageInterface $image
     * @return string
     */
    public function generateThumbHash(ImageInterface $image): string
    {
        $content = $image->resizeDown(100, 100)->toJpeg(20);

        [$width, $height, $pixels] = extract_size_and_pixels_with_imagick($content->toString());
        $hash = Thumbhash::RGBAToHash($width, $height, $pixels);
        $key = Thumbhash::convertHashToString($hash);

        return $key;
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

        $this->disk->deleteDirectory('cocktails/' . $bar->id . '/');
        $this->disk->deleteDirectory('ingredients/' . $bar->id . '/');
    }

    private function processImageFile(ImageInterface $image, ?string $filename = null): array
    {
        $filename = $filename ?? Str::random(40);

        $thumbHash = null;
        try {
            $thumbHashImage = clone $image;
            $thumbHash = $this->generateThumbHash($thumbHashImage);
            unset($thumbHashImage);
        } catch (Throwable $e) {
            $this->log->error('[IMAGE_SERVICE] ThumbHash Error | ' . $e->getMessage());

            throw $e;
        }

        try {
            $image->scaleDown(height: 1400);
            $encodedImage = $image->encode(new AutoEncoder(quality: 85));

            $fileExtension = match ($encodedImage->mediaType()) {
                'image/gif' => 'gif',
                'image/jpeg', 'image/jpg', 'image/pjpeg' => 'jpg',
                'image/webp', 'image/x-webp' => 'webp',
                'image/png', 'image/x-png' => 'png',
                'image/avif', 'image/x-avif' => 'avif',
                'image/bmp',
                'image/ms-bmp',
                'image/x-bitmap',
                'image/x-bmp',
                'image/x-ms-bmp',
                'image/x-win-bitmap',
                'image/x-windows-bmp',
                'image/x-xbitmap' => 'bmp',
                default => 'jpg'
            };

            $filepath = 'temp/' . $filename . '.' . $fileExtension;

            $this->disk->put($filepath, $encodedImage->toString());
        } catch (Throwable $e) {
            $this->log->error('[IMAGE_SERVICE] ' . $e->getMessage());

            throw $e;
        }

        return [$thumbHash, $filepath, $fileExtension];
    }
}
