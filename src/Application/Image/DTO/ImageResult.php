<?php

declare(strict_types=1);

namespace BarAssistant\Application\Image\DTO;

use BarAssistant\Domain\Image\Image;

readonly class ImageResult
{
    public function __construct(
        public int $id,
        public string $path,
    ) {
    }

    public static function fromImage(Image $image): self
    {
        return new self(
            id: $image->getId()->value ?? 0,
            path: $image->getFile()->path,
        );
    }
}
