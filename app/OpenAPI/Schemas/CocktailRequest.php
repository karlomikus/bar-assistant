<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['name', 'instructions'])]
class CocktailRequest
{
    #[OAT\Property(example: 'Cocktail name')]
    public string $name;
    #[OAT\Property(example: 'Step by step instructions')]
    public string $instructions;
    #[OAT\Property(example: 'Cocktail description')]
    public ?string $description = null;
    #[OAT\Property(example: 'Source of the recipe')]
    public ?string $source = null;
    #[OAT\Property(example: 'Garnish')]
    public ?string $garnish = null;
    #[OAT\Property(example: 1, property: 'glass_id')]
    public ?int $glassId = null;
    #[OAT\Property(example: 1, property: 'method_id')]
    public ?int $methodId = null;
    /** @var string[] */
    #[OAT\Property()]
    public array $tags = [];
    #[OAT\Property(type: 'array', items: new OAT\Items(
        type: 'object',
        required: ['ingredient_id', 'units', 'amount'],
        properties: [
            new OAT\Property('ingredient_id', type: 'integer', example: 1),
            new OAT\Property('amount', type: 'number', format: 'float', example: 30),
            new OAT\Property('amount_max', type: 'number', format: 'float', example: 60, nullable: true),
            new OAT\Property('units', type: 'string', example: 'ml'),
            new OAT\Property('optional', type: 'boolean', example: false),
            new OAT\Property('note', type: 'string', example: 'Ingredient note', nullable: true),
            new OAT\Property('substitutes', type: 'array', items: new OAT\Items(
                type: 'object',
                properties: [
                    new OAT\Property('id', type: 'integer', example: 1),
                    new OAT\Property('amount', type: 'number', format: 'float', example: 30, nullable: true),
                    new OAT\Property('amount_max', type: 'number', format: 'float', example: 60, nullable: true),
                    new OAT\Property('units', type: 'string', example: 'ml', nullable: true),
                ],
            )),
        ],
    ))]
    public array $ingredients = [];
    /** @var int[] */
    #[OAT\Property(description: 'List of existing image ids')]
    public array $images = [];
    /** @var int[] */
    #[OAT\Property(description: 'List of existing utensil ids')]
    public array $utensils = [];
}
