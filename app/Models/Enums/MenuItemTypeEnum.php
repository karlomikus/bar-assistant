<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\Enums;

enum MenuItemTypeEnum: string
{
    case Cocktail = 'cocktail';
    case Ingredient = 'ingredient';
}
