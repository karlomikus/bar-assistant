<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Import;

enum DataPackMediaMode: string
{
    case OwnedUpload = 'owned-upload';
    case StarterCatalog = 'starter-catalog';
}
