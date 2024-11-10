<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: [
    'id',
    'name',
    'slug',
    'garnish',
    'description',
    'instructions',
    'source',
    'public_id',
    'public_at',
    'created_at',
    'updated_at',
    'abv',
])]
class Cocktail
{
    #[OAT\Property(example: 1)]
    public int $id;

    #[OAT\Property(example: 'Cocktail name')]
    public string $name;

    #[OAT\Property(example: 'cocktail-name-1')]
    public string $slug;

    #[OAT\Property(example: 'Step by step instructions')]
    public string $instructions;

    #[OAT\Property(example: 'Garnish')]
    public ?string $garnish = null;

    #[OAT\Property(example: 'Cocktail description')]
    public ?string $description = null;

    #[OAT\Property(example: 'Source of the recipe')]
    public ?string $source = null;

    #[OAT\Property(property: 'public_id', example: 'public-id-1')]
    public ?string $publicId = null;

    #[OAT\Property(property: 'public_at', format: 'date-time')]
    public ?string $publicAt = null;

    /** @var Image[] */
    #[OAT\Property()]
    public array $images = [];

    /** @var array<mixed> */
    #[OAT\Property(type: 'array', items: new OAT\Items(
        type: 'object',
        properties: [
            new OAT\Property('id', type: 'integer', example: 1),
            new OAT\Property('name', type: 'string', example: 'Tag name'),
        ],
    ))]
    public array $tags = [];

    /** @var array<mixed> */
    #[OAT\Property(type: 'object', required: ['user', 'average', 'total_votes'], properties: [
        new OAT\Property(type: 'integer', property: 'user', example: 1, nullable: true, description: 'Current user\'s rating'),
        new OAT\Property(type: 'integer', property: 'average', example: 4, description: 'Average rating'),
        new OAT\Property(type: 'integer', property: 'total_votes', example: 12),
    ])]
    public array $rating = [];

    #[OAT\Property()]
    public ?Glass $glass = null;

    /** @var Utensil[] */
    #[OAT\Property()]
    public array $utensils = [];

    /** @var CocktailIngredient[] */
    #[OAT\Property()]
    public array $ingredients = [];

    #[OAT\Property(property: 'created_at', format: 'date-time')]
    public string $createdAt;

    #[OAT\Property(property: 'updated_at', format: 'date-time')]
    public ?string $updatedAt = null;

    #[OAT\Property()]
    public ?CocktailMethod $method = null;

    #[OAT\Property(example: 40.0)]
    public ?float $abv = null;

    #[OAT\Property(property: 'volume_ml', example: 67.5)]
    public ?float $volumeMl = null;

    #[OAT\Property(property: 'alcohol_units', example: 25.5)]
    public ?float $alcoholUnits = null;

    #[OAT\Property(example: 350)]
    public ?int $calories = null;

    #[OAT\Property(property: 'created_user')]
    public UserBasic $createdUser;

    #[OAT\Property(property: 'updated_user')]
    public ?UserBasic $updatedUser = null;

    #[OAT\Property(property: 'in_shelf')]
    public bool $inShelf = false;

    #[OAT\Property(property: 'in_bar_shelf')]
    public bool $inBarShelf = false;

    /** @var array<mixed> */
    #[OAT\Property(type: 'object', required: ['can_edit', 'can_delete', 'can_rate', 'can_add_note'], properties: [
        new OAT\Property(type: 'boolean', property: 'can_edit', example: true),
        new OAT\Property(type: 'boolean', property: 'can_delete', example: true),
        new OAT\Property(type: 'boolean', property: 'can_rate', example: true),
        new OAT\Property(type: 'boolean', property: 'can_add_note', example: true),
    ])]
    public array $access = [];

    /** @var array<mixed> */
    #[OAT\Property(type: 'object', required: ['prev', 'next'], properties: [
        new OAT\Property(type: 'string', nullable: true, property: 'prev', example: 'old-fashioned-1'),
        new OAT\Property(type: 'string', nullable: true, property: 'next', example: 'tom-collins-1'),
    ])]
    public array $navigation = [];
}
