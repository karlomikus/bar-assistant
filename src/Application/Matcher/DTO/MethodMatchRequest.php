<?php

declare(strict_types=1);

namespace BarAssistant\Application\Matcher\DTO;

final readonly class MethodMatchRequest
{
    public function __construct(
        public int $barId,
        public string $methodName,
    ) {
    }
}
