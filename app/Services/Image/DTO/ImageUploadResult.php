<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image\DTO;

final readonly class ImageUploadResult
{
    public function __construct(
        public string $path,
        public string $extension,
        public ?string $placeholderHash = null,
    ) {
    }
}
