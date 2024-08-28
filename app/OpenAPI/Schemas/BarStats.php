<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['total_cocktails', 'total_ingredients', 'total_favorited_cocktails', 'total_shelf_cocktails', 'total_shelf_ingredients', 'total_bar_members', 'total_collections', 'favorite_tags', 'your_top_ingredients', 'most_popular_ingredients', 'top_rated_cocktails'])]
class BarStats
{
    #[OAT\Property(example: 1)]
    public int $total_cocktails;
    #[OAT\Property(example: 1)]
    public int $total_ingredients;
    #[OAT\Property(example: 1)]
    public int $total_favorited_cocktails;
    #[OAT\Property(example: 1)]
    public int $total_shelf_cocktails;
    #[OAT\Property(example: 1)]
    public int $total_shelf_ingredients;
    #[OAT\Property(example: 1)]
    public int $total_bar_members;
    #[OAT\Property(example: 1)]
    public int $total_collections;
    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', required: ['id', 'name', 'cocktails_count'], properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 31),
        new OAT\Property(property: 'name', type: 'string', example: 'Tag name'),
        new OAT\Property(property: 'cocktails_count', type: 'integer', example: 12),
    ]))]
    public array $favorite_tags;
    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', required: ['id', 'slug', 'name', 'cocktails_count'], properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1),
        new OAT\Property(property: 'slug', type: 'string', example: 'gin'),
        new OAT\Property(property: 'name', type: 'string', example: 'Gin'),
        new OAT\Property(property: 'cocktails_count', type: 'integer', example: 1),
    ]))]
    public array $your_top_ingredients;
    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', required: ['id', 'slug', 'name', 'cocktails_count'], properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1),
        new OAT\Property(property: 'slug', type: 'string', example: 'gin'),
        new OAT\Property(property: 'name', type: 'string', example: 'Gin'),
        new OAT\Property(property: 'cocktails_count', type: 'integer', example: 1),
    ]))]
    public array $most_popular_ingredients;
    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', required: ['id', 'slug', 'name', 'avg_rating', 'votes'], properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1),
        new OAT\Property(property: 'slug', type: 'string', example: 'old-fashioned'),
        new OAT\Property(property: 'name', type: 'string', example: 'Old Fashioned'),
        new OAT\Property(property: 'avg_rating', type: 'integer', example: 3),
        new OAT\Property(property: 'votes', type: 'integer', example: 42),
    ]))]
    public array $top_rated_cocktails;
}
