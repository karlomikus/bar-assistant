<?php

declare(strict_types=1);

namespace Kami\Cocktail\Enums;

enum CocktailMethodEnum: int
{
    case Shake = 1;
    case Stir = 2;
    case Build = 3;
    case Blend = 4;
    case Muddle = 5;
    case Layer = 6;
}
