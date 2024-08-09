<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

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
    #[OAT\Property(property: 'default_lang', example: 'en-US', description: 'Locale code, used only as a setting for client apps, does not change language of the API.')]
    public ?string $defaultLang = null;
    #[OAT\Property(property: 'enable_invites', description: 'Enable users with invite code to join this bar. Default `false`.')]
    public bool $enableInvites = true;
    #[OAT\Property(items: new OAT\Items(type: 'string'), description: 'List of data that the bar will start with. Possible values: `ingredients`, `cocktails`. Cocktails cannot be imported without ingredients.')]
    public array $options = ['ingredients', 'cocktails'];
}
