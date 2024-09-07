<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\BarStatusEnum;

#[OAT\Schema(required: ['id', 'slug', 'name', 'subtitle', 'description', 'status', 'access', 'invite_code', 'active', 'settings', 'search_host', 'search_token', 'created_at', 'updated_at'])]
class Bar
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(example: 'bar-name-1')]
    public string $slug;
    #[OAT\Property(example: 'Bar name')]
    public string $name;
    #[OAT\Property(example: 'A short subtitle of a bar')]
    public ?string $subtitle = null;
    #[OAT\Property(example: 'Bar description')]
    public ?string $description = null;
    #[OAT\Property(property: 'invite_code', example: '01H8S3VH2HTEB3D893AW8NTBBC')]
    public ?string $inviteCode;
    #[OAT\Property(example: 'active')]
    public BarStatusEnum $status;
    #[OAT\Property(items: new OAT\Items(type: 'object', additionalProperties: true))]
    public array $settings = [];
    #[OAT\Property(property: 'search_host')]
    public ?string $searchHost = null;
    #[OAT\Property(property: 'search_token')]
    public ?string $searchToken = null;
    #[OAT\Property(property: 'created_at', format: 'date-time')]
    public string $createdAt;
    #[OAT\Property(property: 'updated_at', format: 'date-time')]
    public ?string $updatedAt = null;
    #[OAT\Property(property: 'created_user')]
    public UserBasic $createdUser;
    #[OAT\Property(property: 'updated_user')]
    public ?UserBasic $updatedUser = null;
    #[OAT\Property(type: 'array', items: new OAT\Items(type: 'object', properties: [
        new OAT\Property(type: 'integer', property: 'role_id', example: 1),
        new OAT\Property(type: 'boolean', property: 'can_edit', example: true),
        new OAT\Property(type: 'boolean', property: 'can_delete', example: true),
        new OAT\Property(type: 'boolean', property: 'can_activate', example: true),
        new OAT\Property(type: 'boolean', property: 'can_deactivate', example: true),
    ]))]
    public array $access;
}
