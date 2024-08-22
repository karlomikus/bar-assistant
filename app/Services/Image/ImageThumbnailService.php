<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image;

use Jcupitt\Vips\Image as Vips;

class ImageThumbnailService
{
    public static function generateThumbnailAsJpg(string $filePath, int $size = 400, int $quality = 60): string
    {
        $image = Vips::newFromFile($filePath);

        return $image->thumbnail_image($size)->writeToBuffer('.jpg', ['Q' => $quality]);
    }
}
