<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['missing_prices_count', 'price_category', 'total_price', 'prices_per_ingredient'])]
class CocktailPrice
{
    #[OAT\Property(example: 1, description: 'Number of ingredients that are missing defined prices in this category')]
    public int $missing_prices_count;

    #[OAT\Property()]
    public PriceCategory $price_category;

    #[OAT\Property(description: 'Total cocktail price, sum of `price_per_pour` amounts')]
    public Price $total_price;

    /** @var array<mixed> */
    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', required: ['ingredient', 'price_per_amount', 'price_per_pour'], properties: [
        new OAT\Property(type: IngredientBasic::class, property: 'ingredient'),
        new OAT\Property(type: Price::class, property: 'price_per_amount', description: 'Price per 1 unit of ingredient amount'),
        new OAT\Property(type: Price::class, property: 'price_per_pour', description: 'Price per cocktail ingredient part'),
    ]))]
    public array $prices_per_ingredient;
}
