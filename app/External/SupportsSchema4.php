<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

interface SupportsSchema4
{
    /**
     * @param array<mixed> $sourceArray
     */
    public static function fromSchema4Array(array $sourceArray): self;

    /**
     * @return array<mixed>
     */
    public function toSchema4Array(): array;
}
