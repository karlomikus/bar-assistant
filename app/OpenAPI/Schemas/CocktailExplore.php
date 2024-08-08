<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class CocktailExplore
{
    #[OAT\Property(type: 'object', properties: [
        new OAT\Property(type: 'string', property: 'name', example: 'Bar name'),
        new OAT\Property(type: 'string', property: 'subtitle', example: 'Bar subtitle'),
    ])]
    public array $bar;

    #[OAT\Property(example: 'Cocktail name')]
    public string $name;

    #[OAT\Property(example: 'Step by step instructions')]
    public string $instructions;

    #[OAT\Property(example: 'Garnish')]
    public ?string $garnish = null;

    #[OAT\Property(example: 'Cocktail description')]
    public ?string $description = null;

    #[OAT\Property(example: 'Source of the recipe')]
    public ?string $source = null;

    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', properties: [
        new OAT\Property(type: 'integer', property: 'sort', example: 1),
        new OAT\Property(type: 'string', property: 'placeholder_hash', example: 'a1b2c3d4e5f6g7h8i9j0'),
        new OAT\Property(type: 'string', property: 'url', example: 'https://example.com/image.jpg'),
        new OAT\Property(type: 'string', property: 'copyright', example: 'Image copyright'),
    ]))]
    public array $images = [];

    /** @var string[] */
    #[OAT\Property()]
    public array $tags = [];

    #[OAT\Property()]
    public ?string $glass = null;

    /** @var string[] */
    #[OAT\Property()]
    public array $utensils = [];

    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', properties: [
        new OAT\Property(type: 'integer', property: 'name', example: 'Ingredient name'),
        new OAT\Property(type: 'number', property: 'amount', example: 30.0),
        new OAT\Property(type: 'number', property: 'amount_max', example: 45.0, nullable: true),
        new OAT\Property(type: 'string', property: 'units', example: 'ml'),
        new OAT\Property(type: 'boolean', property: 'optional', example: true),
        new OAT\Property(type: 'string', property: 'note', example: 'Ingredient note', nullable: true),
        new OAT\Property(type: 'array', property: 'substitutes', items: new OAT\Items(type: 'object', properties: [
            new OAT\Property(type: 'string', property: 'name', example: 'Ingredient name'),
            new OAT\Property(type: 'number', property: 'amount', example: 30.0, nullable: true),
            new OAT\Property(type: 'number', property: 'amount_max', example: 45.0, nullable: true),
            new OAT\Property(type: 'string', property: 'units', example: 'ml', nullable: true),
        ])),
    ]))]
    public array $ingredients = [];

    #[OAT\Property()]
    public ?string $method = null;

    #[OAT\Property(example: 40.0)]
    public ?float $abv = null;
}
