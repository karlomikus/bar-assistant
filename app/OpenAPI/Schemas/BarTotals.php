<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['total_cocktails', 'total_ingredients', 'total_favorited_cocktails', 'total_shelf_cocktails', 'total_bar_shelf_ingredients', 'total_bar_shelf_cocktails', 'total_shelf_ingredients', 'total_bar_members', 'total_collections'])]
class BarTotals
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
    public int $total_bar_shelf_ingredients;
    #[OAT\Property(example: 1)]
    public int $total_bar_shelf_cocktails;
    #[OAT\Property(example: 1)]
    public int $total_bar_members;
    #[OAT\Property(example: 1)]
    public int $total_collections;
}
