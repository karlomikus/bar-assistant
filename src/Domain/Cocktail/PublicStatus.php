<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

use DomainException;
use DateTimeImmutable;

final readonly class PublicStatus
{
    private function __construct(
        public ?PublicId $publicId,
        public ?DateTimeImmutable $publicAt,
        public ?DateTimeImmutable $publicExpiresAt,
    ) {
        if ($publicExpiresAt !== null && $publicExpiresAt < $publicAt) {
            throw new DomainException('The expiration date cannot be earlier than the publication date.');
        }
    }

    public static function createNowWithFutureExpirationDate(?DateTimeImmutable $publicExpiresAt): self
    {
        return new self(
            publicId: PublicId::create(),
            publicAt: new DateTimeImmutable(),
            publicExpiresAt: $publicExpiresAt,
        );
    }

    public static function createPublic(): self
    {
        return new self(
            publicId: PublicId::create(),
            publicAt: new DateTimeImmutable(),
            publicExpiresAt: null,
        );
    }

    public static function createPrivate(): self
    {
        return new self(
            publicId: null,
            publicAt: null,
            publicExpiresAt: null,
        );
    }

    public static function createFrom(
        ?PublicId $publicId,
        ?DateTimeImmutable $publicAt,
        ?DateTimeImmutable $publicExpiresAt,
    ): self {
        return new self(
            publicId: $publicId,
            publicAt: $publicAt,
            publicExpiresAt: $publicExpiresAt,
        );
    }
}
