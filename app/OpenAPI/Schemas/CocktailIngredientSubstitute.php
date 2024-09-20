<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['ingredient', 'amount', 'amount_max', 'units', 'in_shelf'])]
class CocktailIngredientSubstitute
{
    #[OAT\Property()]
    public IngredientBasic $ingredient;
    #[OAT\Property(example: 30)]
    public ?float $amount = null;
    #[OAT\Property(property: 'amount_max', example: 60)]
    public ?float $amountMax = null;
    #[OAT\Property(example: 'ml')]
    public ?string $units = null;
    #[OAT\Property(property: 'in_shelf', example: true)]
    public bool $inShelf;
}
