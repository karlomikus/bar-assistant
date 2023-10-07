<?php

declare(strict_types=1);

namespace Kami\Cocktail\Import;

enum DuplicateActionsEnum: int
{
    case None = 0;
    case Skip = 1;
    case Overwrite = 2;
}
