<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\External\ExportTypeEnum;
use Kami\Cocktail\External\ForceUnitConvertEnum;

#[OAT\Schema()]
class ExportRequest
{
    #[OAT\Property()]
    public ExportTypeEnum $type;

    #[OAT\Property()]
    public ForceUnitConvertEnum $units;

    #[OAT\Property(property: 'bar_id')]
    public int $barId;
}
