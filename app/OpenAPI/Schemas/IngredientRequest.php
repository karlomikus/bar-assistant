<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['name'])]
class IngredientRequest
{
    #[OAT\Property(example: 'Gin')]
    public string $name;
    #[OAT\Property(property: 'ingredient_category_id', example: 1)]
    public ?int $ingredientCategoryId = null;
    #[OAT\Property(example: 40.0)]
    public ?float $strength = null;
    #[OAT\Property(example: 'Gin is a type of alcoholic spirit')]
    public ?string $description = null;
    #[OAT\Property(example: 'Worldwide')]
    public ?string $origin = null;
    #[OAT\Property(example: '#ffffff')]
    public ?string $color = null;
    #[OAT\Property(property: 'parent_ingredient_id', example: 1)]
    public int $parentIngredientId;
    /** @var int[] */
    #[OAT\Property(description: 'Existing image ids')]
    public array $images;
    /** @var int[] */
    #[OAT\Property(property: 'complex_ingredient_part_ids', description: 'Existing ingredient ids')]
    public array $complexIngredientPartIds = [];
    /** @var array<mixed> */
    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', properties: [
        new OAT\Property(type: 'integer', property: 'price_category_id', example: 1),
        new OAT\Property(type: 'integer', property: 'price', example: 2500),
        new OAT\Property(type: 'number', property: 'amount', example: 750.00),
        new OAT\Property(type: 'string', property: 'units', example: 'ml'),
        new OAT\Property(type: 'string', property: 'description', example: 'Updated price', nullable: true),
    ]))]
    public array $prices = [];
}
