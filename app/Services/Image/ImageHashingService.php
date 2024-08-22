<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image;

use Thumbhash\Thumbhash;
use Jcupitt\Vips\Image as Vips;

class ImageHashingService
{
    public static function generatePlaceholderHashFromFilepath(string $file, int $width = 100, int $height = 100): string
    {
        $image = Vips::newFromFile($file)->thumbnail_image($width, ['height' => $height, 'crop' => 'centre']);

        if ($image->bands < 4) {
            $image = $image->bandjoin(255);
        }

        $pixels = $image->writeToArray();
        $rgbaPixels = [];

        for ($i = 0; $i < count($pixels); $i += 4) {
            $rgbaPixels[] = $pixels[$i];
            $rgbaPixels[] = $pixels[$i + 1];
            $rgbaPixels[] = $pixels[$i + 2];
            $rgbaPixels[] = $pixels[$i + 3];
        }

        $hash = Thumbhash::RGBAToHash($width, $height, $rgbaPixels);
        $key = Thumbhash::convertHashToString($hash);

        return $key;
    }
}
