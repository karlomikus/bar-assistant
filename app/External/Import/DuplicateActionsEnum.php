<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Import;

use OpenApi\Attributes as OAT;

#[OAT\Schema(type: 'string')]
enum DuplicateActionsEnum: string
{
    case None = 'none';
    case Skip = 'skip';
    case Overwrite = 'overwrite';
}
