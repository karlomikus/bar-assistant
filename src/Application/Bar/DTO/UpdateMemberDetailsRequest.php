<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar\DTO;

final readonly class UpdateMemberDetailsRequest
{
    public function __construct(
        public int $memberId,
    ) {
    }
}
