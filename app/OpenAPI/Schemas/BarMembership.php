<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
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
    #[OAT\Property(property: 'use_parent_as_substitute', example: true)]
    public bool $usParentAsSubstitute;
}