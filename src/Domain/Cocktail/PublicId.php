<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

use Symfony\Component\Uid\Ulid;

final readonly class PublicId
{
    private function __construct(public string $value)
    {
    }

    public static function create(): self
    {
        return new self(Ulid::generate());
    }

    public static function createFrom(string $value): self
    {
        return new self($value);
    }
}
