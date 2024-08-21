<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

interface SupportsDataPack
{
    public static function fromDataPackArray(array $sourceArray): self;
    public function toDataPackArray(): array;
}
