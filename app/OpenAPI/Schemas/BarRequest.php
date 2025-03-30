<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\External\BarOptionsEnum;

#[OAT\Schema(required: ['name'])]
class BarRequest
{
    #[OAT\Property(example: 'Bar name')]
    public string $name;
    #[OAT\Property(example: 'A short subtitle of a bar')]
    public ?string $subtitle = null;
    #[OAT\Property(example: 'Bar description')]
    public ?string $description = null;
    #[OAT\Property(example: 'bar-name-1')]
    public string $slug;
    #[OAT\Property(property: 'default_units', example: 'ml', type: 'string', enum: ['ml', 'cl', 'oz'], description: 'Used only as a setting for client apps.')]
    public ?string $defaultUnits = null;
    #[OAT\Property(property: 'default_currency', example: 'EUR', description: 'ISO 4217 format of currency. Used only as a setting for client apps.')]
    public ?string $defaultCurrency = null;
    #[OAT\Property(property: 'enable_invites', description: 'Enable users with invite code to join this bar. Default `false`.')]
    public bool $enableInvites = true;
    #[OAT\Property(description: 'List of data that the bar will start with. Cocktails cannot be imported without ingredients.')]
    public BarOptionsEnum $options;
    /** @var array<int> */
    #[OAT\Property(items: new OAT\Items(type: 'integer'), description: 'Existing image ids')]
    public array $images = [];
}
