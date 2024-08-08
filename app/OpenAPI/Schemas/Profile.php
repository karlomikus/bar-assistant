<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['id', 'name', 'cocktails_count'])]
class Profile
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(example: 'Floral')]
    public string $name;
    #[OAT\Property(property: 'cocktails_count', example: 12)]
    public string $email;
    #[OAT\Property(property: 'is_subscribed')]
    public bool $isSubscribed = false;
    /** @var BarMembership[] */
    #[OAT\Property()]
    public array $memberships;
}
