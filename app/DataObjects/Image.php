<?php

declare(strict_types=1);

namespace Kami\Cocktail\DataObjects;

use Intervention\Image\Interfaces\ImageInterface;

class Image
{
    public function __construct(
        public readonly ?ImageInterface $file,
        public readonly ?string $copyright = null,
        public readonly ?int $sort = 0,
    ) {
    }
}
