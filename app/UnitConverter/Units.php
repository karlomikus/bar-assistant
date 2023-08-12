<?php

declare(strict_types=1);

namespace Kami\Cocktail\UnitConverter;

enum Units: string
{
    case Cl = 'cl';
    case Ml = 'ml';
    case Oz = 'oz';
    case Dash = 'dash';
}
