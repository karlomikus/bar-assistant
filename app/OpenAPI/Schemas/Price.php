<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['price', 'price_minor', 'formatted_price', 'currency'])]
class Price
{
    #[OAT\Property(example: 13.39)]
    public float $price;
    #[OAT\Property(property: 'price_minor', example: 1339)]
    public int $priceMinor;
    #[OAT\Property(property: 'formatted_price', example: 'EUR 13.39')]
    public string $formattedPrice;
    #[OAT\Property(example: 'EUR')]
    public string $currency;
}
