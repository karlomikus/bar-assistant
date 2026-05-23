<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar\DTO;

final readonly class BarResult
{
    public function __construct(
        public int $id,
        public string $slug,
    ) {
    }
}
