<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use OpenApi\Attributes as OAT;

#[OAT\Schema(type: 'string')]
enum BarStatusEnum: string
{
    case Provisioning = 'provisioning';
    case Active = 'active';
    case Deactivated = 'deactivated';
}
