<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['id', 'name', 'description', 'currency'])]
class PriceCategory
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(example: 'Amazon (DE)')]
    public string $name;
    #[OAT\Property(example: 'Current price on amazon.de')]
    public ?string $description = null;
    #[OAT\Property(example: 'EUR')]
    public string $currency;
    #[OAT\Property(property: 'currency_symbol', example: '€')]
    public string $currencySymbol;
}
