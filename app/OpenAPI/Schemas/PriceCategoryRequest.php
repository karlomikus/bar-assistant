<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['name', 'currency'])]
class PriceCategoryRequest
{
    #[OAT\Property(example: 'Amazon (DE)')]
    public string $name;
    #[OAT\Property(example: 'Current price on amazon.de')]
    public ?string $description = null;
    #[OAT\Property(example: 'EUR', format: 'ISO 4217')]
    public string $currency;
}
