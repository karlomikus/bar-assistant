<?php

declare(strict_types=1);

namespace Kami\Cocktail\DTO\Image;

readonly class Image
{
    public function __construct(
        public ?string $file,
        public ?string $copyright = null,
        public ?int $sort = 0,
    ) {
    }
}
