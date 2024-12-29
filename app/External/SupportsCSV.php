<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

interface SupportsCSV
{
    /**
     * @param array<mixed> $sourceArray
     */
    public static function fromCSV(array $sourceArray): self;
}
