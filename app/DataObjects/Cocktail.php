<?php

declare(strict_types=1);

namespace Kami\Cocktail\DataObjects;

class Cocktail
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly string $instructions,
        public readonly ?string $description = null,
        public readonly ?string $source = null,
        public readonly ?string $garnish = null,
        public readonly ?int $glassId = null,
        public readonly ?int $methodId = null,
        public readonly array $tags = [],
        public readonly array $ingredients = [],
        public readonly array $images = [],
    ) {
    }
}
