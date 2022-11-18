<?php
declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Illuminate\Support\Str;
use Kami\Cocktail\Models\Image;
use Illuminate\Http\UploadedFile;
use Kami\Cocktail\Exceptions\ImageUploadException;

class ImageService
{
    /**
     * Uploads and saves an image with filepath
     *
     * @param array $requestImages
     * @return array<\Kami\Cocktail\Models\Image>
     * @throws ImageUploadException
     */
    public function uploadAndSaveImages(array $requestImages): array
    {
        $images = [];
        foreach ($requestImages as $imageWithMeta) {
            /** @var \Illuminate\Http\UploadedFile */
            $file = $imageWithMeta['image'];

            $filename = Str::random(40);
            $fileExtension = $file->extension();
            $fullFilename = $filename . '.' . $fileExtension;
            $filepath = $file->storeAs('temp', $fullFilename, 'app_images');

            if (!$filepath) {
                throw new ImageUploadException('Unable to store an image file.');
            }

            $image = new Image();
            $image->copyright = $imageWithMeta['copyright'];
            $image->file_path = $filepath;
            $image->file_extension = $fileExtension;
            $image->save();

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

        return $image;
    }
}
