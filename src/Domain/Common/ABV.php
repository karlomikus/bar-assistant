<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Common;

/**
 * Represent ABV strength
 */
final readonly class ABV
{
    private function __construct(private float $abv)
    {
    }

    public static function from(float $abv): self
    {
        if ($abv < 0.0 || $abv > 100.0) {
            throw new \InvalidArgumentException('ABV strength must be between 0.0 and 100.0');
        }

        return new self($abv);
    }

    public function toFloat(): float
    {
        return $this->abv;
    }
}
