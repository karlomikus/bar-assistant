<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

enum UserRoleEnum: int
{
    case Admin = 1;
    case Moderator = 2;
    case General = 3;
    case Guest = 4;
}
