<?php

declare(strict_types=1);

namespace BarAssistant\Application\User\DTO;

use DateTimeImmutable;
use BarAssistant\Domain\User\User;

final readonly class UserResult
{
    /**
     * @param array{language: ?string, theme: ?string} $settings
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?DateTimeImmutable $emailVerifiedAt,
        public array $settings,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
    ) {
    }

    public static function fromEntity(User $user): self
    {
        $userId = $user->getId();
        if ($userId === null) {
            throw new \DomainException('User ID must be set');
        }

        return new self(
            id: $userId->value,
            name: $user->getName()->toString(),
            email: $user->getEmail()->toString(),
            emailVerifiedAt: $user->getEmailVerifiedAt(),
            settings: $user->getSettings()->toArray(),
            createdAt: $user->getCreatedAt(),
            updatedAt: $user->getUpdatedAt(),
        );
    }
}
