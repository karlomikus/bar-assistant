<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image;

use Jcupitt\Vips\Image as Vips;

class ImageResizeService
{
    public static function resizeImageTo(string $fileContent, int $height = 1300): Vips
    {
        $image = Vips::newFromBuffer($fileContent);

        $scale = $height / $image->height;

        if ($image->height > $height) {
            $image = $image->resize($scale);
        }

        return $image;
    }
}
