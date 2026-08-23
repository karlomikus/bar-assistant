<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image;

use Jcupitt\Vips\Image as Vips;

class ImageThumbnailService
{
    public static function generateThumbnail(string $buffer, int $size = 400, int $quality = 60): string
    {
        $image = Vips::newFromBuffer($buffer);

        return $image->thumbnail_image($size)->writeToBuffer('.webp', ['Q' => $quality]);
    }
}
