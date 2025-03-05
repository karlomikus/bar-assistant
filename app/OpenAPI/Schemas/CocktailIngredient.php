<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['ingredient', 'sort', 'amount', 'units', 'formatted'])]
class CocktailIngredient
{
    #[OAT\Property()]
    public IngredientBasic $ingredient;
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
    #[OAT\Property(property: 'is_specified', example: false)]
    public bool $isSpecified;
    /** @var CocktailIngredientSubstitute[] */
    #[OAT\Property()]
    public array $substitutes = [];
    /** @var IngredientBasic[] */
    #[OAT\Property(property: 'variants_in_shelf')]
    public array $variantsInShelf = [];
    #[OAT\Property(example: 'Additional notes')]
    public ?string $note = null;
    /** @var array<mixed> */
    #[OAT\Property(
        type: 'object',
        required: ['ml', 'oz', 'cl'],
        properties: [
            new OAT\Property('ml', type: 'object', required: ['amount', 'amount_max', 'units', 'full_text'], properties: [
                new OAT\Property('amount', type: 'number', format: 'float', example: 30),
                new OAT\Property('amount_max', type: 'number', format: 'float', example: 60),
                new OAT\Property('units', type: 'string', example: 'ml'),
                new OAT\Property('full_text', type: 'string', example: '30-60 ml'),
            ]),
            new OAT\Property('oz', type: 'object', required: ['amount', 'amount_max', 'units', 'full_text'], properties: [
                new OAT\Property('amount', type: 'number', format: 'float', example: 1),
                new OAT\Property('amount_max', type: 'number', format: 'float', example: 2),
                new OAT\Property('units', type: 'string', example: 'oz'),
                new OAT\Property('full_text', type: 'string', example: '1-2 oz'),
            ]),
            new OAT\Property('cl', type: 'object', required: ['amount', 'amount_max', 'units', 'full_text'], properties: [
                new OAT\Property('amount', type: 'number', format: 'float', example: 3),
                new OAT\Property('amount_max', type: 'number', format: 'float', example: 6),
                new OAT\Property('units', type: 'string', example: 'cl'),
                new OAT\Property('full_text', type: 'string', example: '3-6 cl'),
            ]),
        ],
        additionalProperties: true,
        description: 'Amounts in different units, converted if possible'
    )]
    public array $formatted = [];
    #[OAT\Property(property: 'in_shelf', example: true)]
    public bool $inShelf = false;
    #[OAT\Property(property: 'in_shelf_as_variant', example: true)]
    public bool $inShelfAsVariant = false;
    #[OAT\Property(property: 'in_shelf_as_substitute', example: true)]
    public bool $inShelfAsSubstitute = false;
    #[OAT\Property(property: 'in_shelf_as_complex_ingredient', example: true)]
    public bool $inShelfAsComplexIngredient = false;
    #[OAT\Property(property: 'in_bar_shelf', example: true)]
    public bool $inBarShelf = false;
    #[OAT\Property(property: 'in_bar_shelf_as_substitute', example: true)]
    public bool $inBarShelfAsSubstitute = false;
    #[OAT\Property(property: 'in_bar_shelf_as_complex_ingredient', example: true)]
    public bool $inBarShelfAsComplexIngredient = false;
    #[OAT\Property(property: 'in_bar_shelf_as_variant', example: true)]
    public bool $inBarShelfAsVariant = false;
}
