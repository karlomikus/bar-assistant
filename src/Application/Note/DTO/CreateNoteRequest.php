<?php

declare(strict_types=1);

namespace BarAssistant\Application\Note\DTO;

final readonly class CreateNoteRequest
{
    public function __construct(
        public int $userId,
        public int $resourceId,
        public string $resource,
        public string $note,
    ) {
    }
}
