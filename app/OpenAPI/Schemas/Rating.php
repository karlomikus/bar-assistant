<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class Rating
{
    #[OAT\Property(property: 'rateable_id', example: 1)]
    public int $rateableId;
    #[OAT\Property(property: 'user_id', example: 1)]
    public int $userId;
    #[OAT\Property(example: 3)]
    public int $rating;
}
