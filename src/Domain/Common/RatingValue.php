<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Common;

use BarAssistant\Domain\Exception\DomainException;

final readonly class RatingValue
{
    private function __construct(public int $value)
    {
        if ($value < 1 || $value > 5) {
            throw new DomainException('Rating value must be between 1 and 5');
        }
    }

    public static function create(int $rating): self
    {
        return new self($rating);
    }
}
