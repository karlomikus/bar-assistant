<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\Enums\BarStatusEnum;

#[OAT\Schema(
    description: 'Details about a bar',
    required: ['id', 'slug', 'name', 'subtitle', 'description', 'status', 'access', 'invite_code', 'settings', 'search_host', 'search_token', 'created_at', 'updated_at']
)]
class Bar
{
    #[OAT\Property(example: 1, description: 'Unique number that can be used to reference a specific bar.')]
    public int $id;
    #[OAT\Property(example: 'bar-name-1', description: 'Unique string that can be used to reference a specific bar.')]
    public string $slug;
    #[OAT\Property(example: 'Bar name', description: 'Name of the bar')]
    public string $name;
    #[OAT\Property(example: 'A short subtitle of a bar', description: 'Optional short quip about the bar')]
    public ?string $subtitle = null;
    #[OAT\Property(example: 'Bar description', description: 'Description of the bar')]
    public ?string $description = null;
    #[OAT\Property(property: 'invite_code', example: '01H8S3VH2HTEB3D893AW8NTBBC', description: 'Random code used to invite people to the bar')]
    public ?string $inviteCode;
    #[OAT\Property(example: 'active', description: 'Current status of the bar')]
    public BarStatusEnum $status;
    #[OAT\Property(description: 'Settings for the bar')]
    public BarSettings $settings;
    #[OAT\Property(property: 'search_host', description: 'Host URL used to access the bar\'s search engine')]
    public ?string $searchHost = null;
    #[OAT\Property(property: 'search_token', description: 'Auth token used to access the bar\'s search engine')]
    public ?string $searchToken = null;
    #[OAT\Property(property: 'created_at', format: 'date-time', description: 'Date and time when the bar was created')]
    public string $createdAt;
    #[OAT\Property(property: 'updated_at', format: 'date-time', description: 'Date and time when the bar was last updated')]
    public ?string $updatedAt = null;
    #[OAT\Property(property: 'created_user', description: 'User who created the bar')]
    public UserBasic $createdUser;
    #[OAT\Property(property: 'updated_user', description: 'User who last updated the bar')]
    public ?UserBasic $updatedUser = null;
    /** @var array<mixed> */
    #[OAT\Property(type: 'object', properties: [
        new OAT\Property(type: 'integer', property: 'role_id', example: 1),
        new OAT\Property(type: 'boolean', property: 'can_edit', example: true),
        new OAT\Property(type: 'boolean', property: 'can_delete', example: true),
        new OAT\Property(type: 'boolean', property: 'can_activate', example: true),
        new OAT\Property(type: 'boolean', property: 'can_deactivate', example: true),
    ], description: 'User access rights for the bar')]
    public array $access;
    /** @var Image[] */
    #[OAT\Property()]
    public array $images = [];
}
