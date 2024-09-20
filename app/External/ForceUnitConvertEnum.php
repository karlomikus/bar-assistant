<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

use OpenApi\Attributes as OAT;

#[OAT\Schema(type: 'string')]
enum ForceUnitConvertEnum: string
{
    case Original = 'none';
    case Ml = 'ml';
    case Oz = 'oz';
    case Cl = 'cl';
}
