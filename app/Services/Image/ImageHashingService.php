<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image;

use Thumbhash\Thumbhash;
use Intervention\Image\Interfaces\ImageInterface;
use function Thumbhash\extract_size_and_pixels_with_imagick;

class ImageHashingService
{
    /**
     * Generates ThumbHash key
     * @see https://evanw.github.io/thumbhash/
     *
     * @param ImageInterface $image
     * @return string
     */
    public function generatePlaceholderHash(ImageInterface $image): string
    {
        $content = $image->resizeDown(100, 100)->toJpeg(20);

        [$width, $height, $pixels] = extract_size_and_pixels_with_imagick($content->toString());
        $hash = Thumbhash::RGBAToHash($width, $height, $pixels);
        $key = Thumbhash::convertHashToString($hash);

        return $key;
    }
}