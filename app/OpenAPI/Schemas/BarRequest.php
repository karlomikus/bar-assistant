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
    #[OAT\Property(property: 'default_units', example: 'ml')]
    public ?string $defaultUnits = null;
    #[OAT\Property(property: 'default_lang', example: 'en-US')]
    public ?string $defaultLang = null;
    #[OAT\Property(property: 'enable_invites')]
    public bool $enableInvites = true;
    #[OAT\Property(items: new OAT\Items(type: 'string'))]
    public array $options = ['ingredients', 'cocktails'];
}