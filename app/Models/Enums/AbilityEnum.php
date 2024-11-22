<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\Enums;

use OpenApi\Attributes as OAT;

#[OAT\Schema(type: 'string')]
enum AbilityEnum: string
{
    case CocktailsRead = 'cocktails.read';
    case CocktailsWrite = 'cocktails.write';
    case IngredientsRead = 'ingredients.read';
    case IngredientsWrite = 'ingredients.write';
}
