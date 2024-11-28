<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\Enums\AbilityEnum;

#[OAT\Schema(required: ['abilities'])]
class PersonalAccessTokenRequest
{
    #[OAT\Property(example: 'user_generated')]
    public ?string $name = null;
    /** @var AbilityEnum[] */
    #[OAT\Property(type: 'array', items: new OAT\Items(ref: AbilityEnum::class))]
    public array $abilities;
    #[OAT\Property(example: '2023-05-14T21:23:40.000000Z', property: 'expires_at')]
    public ?string $expiresAt = null;
}
