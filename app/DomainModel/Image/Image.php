<?php

declare(strict_types=1);

namespace Kami\Cocktail\DomainModel\Image;

use Intervention\Image\Interfaces\ImageInterface;

readonly class Image
{
    public function __construct(
        public ?ImageInterface $file,
        public ?string $copyright = null,
        public ?int $sort = 0,
    ) {
    }
}
