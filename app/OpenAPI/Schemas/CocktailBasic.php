<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(description: 'Minimal cocktail information', required: ['id', 'slug', 'name'])]
class CocktailBasic
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(example: 'old-fashioned-1')]
    public string $slug;
    #[OAT\Property(example: 'Old fashioned')]
    public string $name;
    /** @var array<string> */
    #[OAT\Property(type: 'array', property: 'short_ingredients', items: new OAT\Items(type: 'string', example: 'Vodka'))]
    public array $shortIngredients;
    #[OAT\Property(description: 'Main resource image')]
    public Image $image;
}
