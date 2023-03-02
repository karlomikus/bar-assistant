<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Throwable;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Kami\Cocktail\Models\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use Kami\Cocktail\DataObjects\Image as ImageDTO;
use Kami\Cocktail\Exceptions\ImageUploadException;

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
     * @param array<\Kami\Cocktail\DataObjects\Image> $requestImages
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

            $filename = Str::random(40);
            /** @phpstan-ignore-next-line */
            $fileExtension = $dtoImage->file->extension ?? 'jpg';
            $filepath = 'temp/' . $filename . '.' . $fileExtension;

            try {
                $this->disk->put($filepath, (string) $dtoImage->file->encode());
            } catch (Throwable $e) {
                $this->log->info('[IMAGE_SERVICE] ' . $e->getMessage());
                continue;
            }

            $image = new Image();
            $image->copyright = $dtoImage->copyright;
            $image->file_path = $filepath;
            $image->file_extension = $fileExtension;
            $image->user_id = $userId;
            $image->sort = $dtoImage->sort;
            $image->save();

            $this->log->info('[IMAGE_SERVICE] Image created with id: ' . $image->id);

            $images[] = $image;
        }

        return $images;
    }

    /**
     * Update image by id
     *
     * @param \Kami\Cocktail\DataObjects\Image $imageDTO Image object
     * @return \Kami\Cocktail\Models\Image Database image model
     */
    public function updateImage(ImageDTO $imageDTO): Image
    {
        $image = Image::findOrFail($imageDTO->id);

        if ($imageDTO->copyright) {
            $image->copyright = $imageDTO->copyright;
        }

        if ($imageDTO->sort) {
            $image->sort = $imageDTO->sort;
        }

        $image->save();

        $this->log->info('[IMAGE_SERVICE] Image updated with id: ' . $image->id);

        return $image;
    }
}
