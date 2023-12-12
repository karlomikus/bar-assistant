<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

enum BarStatusEnum: string
{
    case Provisioning = 'provisioning';
    case Active = 'active';
    case Deactivated = 'deactivated';
}
