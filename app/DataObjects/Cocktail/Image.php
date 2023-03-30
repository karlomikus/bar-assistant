<?php

declare(strict_types=1);

namespace Kami\Cocktail\DataObjects\Cocktail;

use Intervention\Image\Image as InterventionImage;

class Image
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?InterventionImage $file,
        public readonly ?string $copyright = null,
        public readonly int $sort = 0,
    ) {
    }
}
