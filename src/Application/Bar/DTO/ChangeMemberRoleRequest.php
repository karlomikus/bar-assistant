<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar\DTO;

final readonly class ChangeMemberRoleRequest
{
    public function __construct(
        public int $memberId,
        public int $roleId,
    ) {
    }
}
