<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

use OpenApi\Attributes as OAT;

#[OAT\Schema(type: 'string')]
enum BarOptionsEnum: string
{
    case Ingredients = 'ingredients';
    case Cocktails = 'cocktails';
}
