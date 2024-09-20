<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

interface SupportsDataPack
{
    /**
     * @param array<mixed> $sourceArray
     */
    public static function fromDataPackArray(array $sourceArray): self;

    /**
     * @return array<mixed>
     */
    public function toDataPackArray(): array;
}
