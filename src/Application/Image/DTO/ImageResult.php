<?php

declare(strict_types=1);

namespace BarAssistant\Application\Image\DTO;

use BarAssistant\Domain\Image\Image;

readonly class ImageResult
{
    public function __construct(
        public string $path,
        public int $sort,
        public ?string $copyright = null,
    ) {
    }

    public static function fromImage(Image $image): self
    {
        return new self(
            path: $image->getPath(),
            sort: $image->getSort(),
            copyright: $image->getCopyright(),
        );
    }
}
