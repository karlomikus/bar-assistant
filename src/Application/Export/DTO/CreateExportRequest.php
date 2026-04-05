<?php

declare(strict_types=1);

namespace BarAssistant\Application\Export\DTO;

final readonly class CreateExportRequest
{
    public function __construct(
        public int $barId,
        public int $userId,
        public string $filename,
    ) {
    }
}
