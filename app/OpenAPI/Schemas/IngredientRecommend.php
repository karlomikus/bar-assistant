<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Http\Resources\IngredientBasicResource;

#[OAT\Schema(description: 'Ingredient recommendation with number of potential cocktails')]
class IngredientRecommend extends IngredientBasicResource
{
    #[OAT\Property(property: 'potential_cocktails', example: 10)]
    public int $potentialCocktails;
}
