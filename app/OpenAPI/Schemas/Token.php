<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class Token
{
    #[OAT\Property(example: '1|dvWHLWuZbmWWFbjaUDla393Q9jK5Ou9ujWYPcvII')]
    public string $token;
}
