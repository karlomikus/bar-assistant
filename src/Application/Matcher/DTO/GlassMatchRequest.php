<?php

declare(strict_types=1);

namespace BarAssistant\Application\Matcher\DTO;

final readonly class GlassMatchRequest
{
    public function __construct(
        public int $barId,
        public string $glassName,
    ) {
    }
}
