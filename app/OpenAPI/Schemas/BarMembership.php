<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['user_id', 'user_name', 'bar_id', 'is_shelf_public'])]
class BarMembership
{
    #[OAT\Property(property: 'user_id', example: 1)]
    public int $userId;
    #[OAT\Property(property: 'user_name', example: 'Bartender')]
    public string $userName;
    #[OAT\Property(property: 'bar_id', example: 1)]
    public int $barId;
    #[OAT\Property(property: 'is_shelf_public', example: true)]
    public bool $isShelfPublic;
}
