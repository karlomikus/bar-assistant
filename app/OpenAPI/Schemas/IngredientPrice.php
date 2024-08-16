<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class IngredientPrice
{
    #[OAT\Property(property: 'price_category')]
    public PriceCategory $priceCategory;
    #[OAT\Property()]
    public Price $price;
    #[OAT\Property(example: 30.0)]
    public float $amount;
    #[OAT\Property(example: 'ml')]
    public string $units;
    #[OAT\Property(example: 'Updated price')]
    public ?string $description = null;
    #[OAT\Property(property: 'created_at', format: 'date-time')]
    public string $createdAt;
    #[OAT\Property(property: 'updated_at', format: 'date-time')]
    public ?string $updatedAt = null;
}
