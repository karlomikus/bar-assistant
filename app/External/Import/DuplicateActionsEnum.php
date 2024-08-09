<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Import;

use OpenApi\Attributes as OAT;

#[OAT\Schema(type: 'integer')]
enum DuplicateActionsEnum: int
{
    case None = 0;
    case Skip = 1;
    case Overwrite = 2;
}
