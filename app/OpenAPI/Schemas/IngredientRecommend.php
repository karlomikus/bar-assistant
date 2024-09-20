<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(description: 'Ingredient recommendation with number of potential cocktails')]
class IngredientRecommend extends IngredientBasic
{
    #[OAT\Property(property: 'potential_cocktails', example: 10)]
    public int $potentialCocktails;
}
