<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Import;

enum ExistingResourcePolicy: string
{
    case SkipExisting = 'skip-existing';
    case FillMissingScalars = 'fill-missing-scalars';
}
