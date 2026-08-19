<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Common;

use BarAssistant\Domain\Exception\DomainException;

final readonly class RatingValue
{
    private function __construct(public float $value)
    {
        if ($value < 1 || $value > 5) {
            throw new DomainException('Rating value must be between 1 and 5');
        }

        if (fmod($value * 2, 1.0) !== 0.0) {
            throw new DomainException('Rating value must be on a 0.5 step');
        }
    }

    public static function create(float $rating): self
    {
        return new self($rating);
    }
}
