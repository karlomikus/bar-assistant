<?php

declare(strict_types=1);

namespace BarAssistant\Application\Image\DTO;

readonly class CreateImage
{
    public function __construct(
        public string $imageFilePath,
        public string $imageFileExtension,
        public int $userId,
        public int $sort = 1,
        public ?string $copyright = null,
        public ?string $placeholderHash = null,
    ) {
    }
}
