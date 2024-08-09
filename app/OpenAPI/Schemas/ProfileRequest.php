<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['name', 'email'])]
class ProfileRequest
{
    #[OAT\Property(property: 'bar_id')]
    public ?int $barId = null;
    #[OAT\Property(example: 'Bar Tender')]
    public string $name;
    #[OAT\Property(example: 'new@email.com')]
    public string $email;
    #[OAT\Property(example: 'newpassword', format: 'password')]
    public ?string $password = null;
    #[OAT\Property(property: 'is_shelf_public')]
    public bool $isShellfPublic = false;
    #[OAT\Property(property: 'use_parent_as_substitute')]
    public bool $useParentAsSubstitute = false;
}
