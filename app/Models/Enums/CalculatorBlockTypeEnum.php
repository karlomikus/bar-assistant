<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\Enums;

use OpenApi\Attributes as OAT;

#[OAT\Schema(type: 'string')]
enum CalculatorBlockTypeEnum: string
{
    case Input = 'input';
    case Eval = 'eval';
}
