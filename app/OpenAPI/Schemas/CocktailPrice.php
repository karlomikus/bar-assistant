<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Http\Resources\PriceResource;
use Kami\Cocktail\Http\Resources\PriceCategoryResource;
use Kami\Cocktail\Http\Resources\IngredientBasicResource;

#[OAT\Schema(required: ['missing_prices_count', 'price_category', 'total_price', 'prices_per_ingredient'])]
class CocktailPrice
{
    #[OAT\Property(example: 1, description: 'Number of ingredients that are missing defined prices in this category')]
    public int $missing_prices_count;

    #[OAT\Property()]
    public PriceCategoryResource $price_category;

    #[OAT\Property(description: 'Total cocktail price, sum of `price_per_pour` amounts')]
    public PriceResource $total_price;

    /** @var array<mixed> */
    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', required: ['ingredient', 'price_per_unit', 'price_per_use', 'units'], properties: [
        new OAT\Property(type: IngredientBasicResource::class, property: 'ingredient'),
        new OAT\Property(type: 'string', property: 'units', description: 'Units used for price calculation'),
        new OAT\Property(type: PriceResource::class, property: 'price_per_unit', description: 'Price per 1 unit of ingredient amount'),
        new OAT\Property(type: PriceResource::class, property: 'price_per_use', description: 'Price per cocktail ingredient part'),
    ]))]
    public array $prices_per_ingredient;
}
