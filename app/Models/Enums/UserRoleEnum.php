<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\Enums;

enum UserRoleEnum: int
{
    case Admin = 1;
    case General = 3;
    case Guest = 4;
}
