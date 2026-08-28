<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Common;

use BarAssistant\Domain\Exception\DomainException;

final readonly class ReviewContent
{
    private function __construct(public string $value)
    {
    }

    public static function create(string $content): self
    {
        $trimmed = trim($content);

        if ($trimmed === '') {
            throw new DomainException('Review content must not be empty');
        }

        if (mb_strlen($trimmed) > 5000) {
            throw new DomainException('Review content must not exceed 5000 characters');
        }

        return new self($trimmed);
    }
}
