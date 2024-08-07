<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
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

    #[OAT\Property(property: 'main_image_id', example: 1)]
    public ?string $mainImageId = null;

    #[OAT\Property(property: 'created_at', format: 'date-time')]
    public string $createdAt;

    #[OAT\Property(property: 'updated_at', format: 'date-time')]
    public ?string $updatedAt = null;

    /** @var Image[] */
    #[OAT\Property()]
    public array $images = [];

    #[OAT\Property(property: 'parent_ingredient', type: 'object', properties: [
        new OAT\Property(type: 'integer', property: 'id', example: 1),
        new OAT\Property(type: 'string', property: 'slug', example: 'vodka-1'),
        new OAT\Property(type: 'string', property: 'name', example: 'Vodka'),
    ])]
    public array $parentIngredient = [];

    #[OAT\Property(example: '#ffffff')]
    public string $color;

    #[OAT\Property()]
    public ?IngredientCategory $category = null;

    #[OAT\Property(property: 'cocktails_count', example: 12)]
    public int $cocktailsCount;

    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', properties: [
        new OAT\Property(type: 'integer', property: 'id', example: 1),
        new OAT\Property(type: 'string', property: 'slug', example: 'vodka-1'),
        new OAT\Property(type: 'string', property: 'name', example: 'Vodka'),
    ]))]
    public array $varieties = [];

    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', properties: [
        new OAT\Property(type: 'integer', property: 'id', example: 1),
        new OAT\Property(type: 'string', property: 'slug', example: 'old-fashioned-1'),
        new OAT\Property(type: 'string', property: 'name', example: 'Old fashioned'),
    ]))]
    public array $cocktails = [];

    #[OAT\Property(property: 'created_user')]
    public UserBasic $createdUser;

    #[OAT\Property(property: 'updated_user')]
    public ?UserBasic $updatedUser = null;

    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', properties: [
        new OAT\Property(type: 'boolean', property: 'can_edit', example: true),
        new OAT\Property(type: 'boolean', property: 'can_delete', example: true),
    ]))]
    public array $access = [];

    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', properties: [
        new OAT\Property(type: 'integer', property: 'id', example: 1),
        new OAT\Property(type: 'string', property: 'slug', example: 'vodka-1'),
        new OAT\Property(type: 'string', property: 'name', example: 'Vodka'),
    ]))]
    public array $ingredientParts = [];

    /** @var IngredientPrice[] */
    #[OAT\Property()]
    public array $prices = [];
}
