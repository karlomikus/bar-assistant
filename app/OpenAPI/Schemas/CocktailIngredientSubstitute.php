<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class CocktailIngredientSubstitute
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(example: 'vodka')]
    public string $slug;
    #[OAT\Property(example: 'Vodka')]
    public string $name;
    #[OAT\Property(example: 30)]
    public ?float $amount = null;
    #[OAT\Property(property: 'amount_max', example: 60)]
    public ?float $amountMax = null;
    #[OAT\Property(example: 'ml')]
    public ?string $units = null;
    #[OAT\Property(property: 'in_shelf', example: true)]
    public bool $inShelf;
}
