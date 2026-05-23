<?php

declare(strict_types=1);

namespace BarAssistant\Application\Image\DTO;

readonly class DeleteImageRequest
{
    public function __construct(
        public int $id,
    ) {
    }
}
