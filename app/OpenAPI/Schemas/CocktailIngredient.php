<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class CocktailIngredient
{
    #[OAT\Property(example: 0)]
    public int $sort = 0;
    #[OAT\Property(example: 30)]
    public float $amount;
    #[OAT\Property(property: 'amount_max', example: 60)]
    public ?float $amountMax = null;
    #[OAT\Property(example: 'ml')]
    public string $units;
    #[OAT\Property(example: false)]
    public bool $optional;
    #[OAT\Property(example: 1)]
    public int $ingredientId;
    #[OAT\Property(example: 'Vodka')]
    public string $name;
    #[OAT\Property(example: 'vodka-1')]
    public string $ingredientSlug;
    /** @var CocktailIngredientSubstitute[] */
    #[OAT\Property()]
    public array $substitutes = [];
    #[OAT\Property(example: 'Additional notes')]
    public ?string $note = null;
    // TODO: Key-value pair definition
    #[OAT\Property(type: 'array', items: new OAT\Items(
        type: 'object',
        properties: [
            new OAT\Property('amount', type: 'number', format: 'float', example: 30),
            new OAT\Property('amount_max', type: 'number', format: 'float', example: 60),
            new OAT\Property('units', type: 'string', example: 'ml'),
            new OAT\Property('full_text', type: 'string', example: '30-60 ml'),
        ],
    ), description: 'Amounts in different units, converted if possible')]
    public array $formatted = [];
    #[OAT\Property(property: 'in_shelf', example: true)]
    public bool $inShelf = false;
    #[OAT\Property(property: 'in_shelf_as_substitute', example: true)]
    public bool $inShelfAsSubstitute = false;
    #[OAT\Property(property: 'in_shelf_as_complex_ingredient', example: true)]
    public bool $inShelfAsComplexIngredient = false;
}
