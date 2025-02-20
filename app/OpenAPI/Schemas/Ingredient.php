<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['id', 'slug', 'name', 'description', 'origin', 'color', 'created_at', 'updated_at', 'strength', 'hierarchy', 'materialized_path'])]
class Ingredient
{
    #[OAT\Property(example: 1)]
    public int $id;

    #[OAT\Property(example: 'gin-1')]
    public string $slug;

    #[OAT\Property(example: 'Gin')]
    public string $name;

    #[OAT\Property(example: 40.0)]
    public float $strength = 0.0;

    #[OAT\Property(example: 'Gin is a type of alcoholic spirit')]
    public ?string $description = null;

    #[OAT\Property(example: 'Worldwide')]
    public ?string $origin = null;

    #[OAT\Property(property: 'created_at', format: 'date-time')]
    public string $createdAt;

    #[OAT\Property(property: 'updated_at', format: 'date-time')]
    public ?string $updatedAt = null;

    #[OAT\Property(property: 'materialized_path')]
    public ?string $materializedPath = null;

    /** @var Image[] */
    #[OAT\Property()]
    public array $images = [];

    #[OAT\Property(example: '#ffffff')]
    public string $color;

    #[OAT\Property(property: 'cocktails_count', example: 12)]
    public int $cocktailsCount;

    /** @var array<mixed> */
    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', properties: [
        new OAT\Property(type: 'integer', property: 'id', example: 1),
        new OAT\Property(type: 'string', property: 'slug', example: 'old-fashioned-1'),
        new OAT\Property(type: 'string', property: 'name', example: 'Old fashioned'),
    ]))]
    public array $cocktails = [];

    #[OAT\Property(property: 'created_user')]
    public UserBasic $createdUser;

    #[OAT\Property()]
    public IngredientHierarchy $hierarchy;

    #[OAT\Property(property: 'updated_user')]
    public ?UserBasic $updatedUser = null;

    /** @var array<mixed> */
    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', properties: [
        new OAT\Property(type: 'boolean', property: 'can_edit', example: true),
        new OAT\Property(type: 'boolean', property: 'can_delete', example: true),
    ]))]
    public array $access = [];

    /** @var IngredientBasic[] */
    #[OAT\Property(property: 'ingredient_parts')]
    public array $ingredientParts = [];

    /** @var IngredientPrice[] */
    #[OAT\Property()]
    public array $prices = [];

    #[OAT\Property(property: 'in_shelf')]
    public bool $inShelf = false;

    #[OAT\Property(property: 'in_shelf_as_variant')]
    public bool $inShelfAsVariant = false;

    #[OAT\Property(property: 'in_bar_shelf')]
    public bool $inBarShelf = false;

    #[OAT\Property(property: 'in_shopping_list')]
    public bool $inShoppingList = false;

    #[OAT\Property(property: 'in_bar_shelf_as_variant')]
    public bool $inBarShelfAsVariant = false;

    /** @var IngredientBasic[] */
    #[OAT\Property(property: 'used_as_substitute_for')]
    public array $usedAsSubstituteFor = [];

    /** @var IngredientBasic[] */
    #[OAT\Property(property: 'can_be_substituted_with')]
    public array $canBeSubstitutedWith = [];

    #[OAT\Property(property: 'calculator_id')]
    public ?int $calculatorId = null;
}
