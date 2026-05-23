<?php

declare(strict_types=1);

namespace BarAssistant\Application\Image\DTO;

readonly class UpdateImageRequest
{
    public function __construct(
        public int $id,
        public int $userId,
        public int $sort = 1,
        public ?string $imageFilePath = null,
        public ?string $imageFileExtension = null,
        public ?string $copyright = null,
        public ?string $placeholderHash = null,
    ) {
    }
}
