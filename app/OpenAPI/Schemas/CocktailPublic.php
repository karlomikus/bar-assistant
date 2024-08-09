<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class CocktailPublic
{
    #[OAT\Property(property: 'public_id', example: '01ARZ3NDEKTSV4RRFFQ69G5FAV')]
    public string $publicId;
    #[OAT\Property(property: 'public_at', format: 'datetime', example: '2023-05-14T21:23:40.000000Z')]
    public string $publicAt;
    #[OAT\Property(property: 'public_expires_at', format: 'datetime', example: '2023-05-14T21:23:40.000000Z')]
    public string $publicExpiresAt;
}
