<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image\DTO;

final readonly class ImageCleanupResult
{
    public function __construct(
        public int $deletedImages,
        public int $deletedTemporaryFiles,
    ) {
    }
}
