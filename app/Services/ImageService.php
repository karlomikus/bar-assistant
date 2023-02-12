<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Kami\Cocktail\Models\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\Exceptions\ImageUploadException;
use Intervention\Image\ImageManagerStatic as InterventionImage;

class ImageService
{
    public function __construct(
        private readonly LogManager $log,
    ) {
    }

    /**
     * Uploads and saves an image with filepath
     *
     * @param array<mixed> $requestImages
     * @param int $userId
     * @return array<\Kami\Cocktail\Models\Image>
     * @throws ImageUploadException
     */
    public function uploadAndSaveImages(array $requestImages, int $userId): array
    {
        $images = [];
        foreach ($requestImages as $imageWithMeta) {
            $filename = Str::random(40);

            $file = $imageWithMeta['image'];
            if (!($file instanceof UploadedFile)) {
                $tempImage = InterventionImage::make($file);
                $fileExtension = 'jpg';
                $filepath = 'temp/' . $filename . '.' . $fileExtension;
                $saveFilePath = Storage::disk('bar-assistant')->path($filepath);
                $tempImage->save($saveFilePath);
            } else {
                $fileExtension = $file->extension();
                $fullFilename = $filename . '.' . $fileExtension;
                $filepath = $file->storeAs('temp', $fullFilename, 'bar-assistant');
            }

            if (!$filepath) {
                throw new ImageUploadException('Unable to store an image file.');
            }

            $image = new Image();
            $image->copyright = $imageWithMeta['copyright'];
            $image->file_path = $filepath;
            $image->file_extension = $fileExtension;
            $image->user_id = $userId;
            $image->save();

            $this->log->info('Image created with id: ' . $image->id);

            $images[] = $image;
        }

        return $images;
    }

    public function updateImage(int $imageId, ?UploadedFile $file = null, ?string $copyright = null): Image
    {
        $image = Image::findOrFail($imageId);

        if ($copyright) {
            $image->copyright = $copyright;
        }

        $image->save();

        $this->log->info('Image updated with id: ' . $image->id);

        return $image;
    }

    public function uploadImage(string $imageSource, int $userId, ?string $copyright = null): Image
    {
        $tempImage = InterventionImage::make($imageSource);
        $filepath = 'temp/' . Str::random(40) . '.jpg';
        $saveFilePath = Storage::disk('bar-assistant')->path($filepath);
        $tempImage->save($saveFilePath);

        $image = new Image();
        $image->copyright = $copyright;
        $image->file_path = $filepath;
        $image->file_extension = 'jpg';
        $image->user_id = $userId;
        $image->save();

        $this->log->info('Image created with id: ' . $image->id);

        return $image;
    }
}
