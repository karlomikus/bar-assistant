<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\Enums\BarStatusEnum;
use Kami\Cocktail\OpenAPI\Schemas\BarSettings;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Bar
 */
#[OAT\Schema(
    schema: 'Bar',
    description: 'Details about a bar',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1, description: 'Unique number that can be used to reference a specific bar.'),
        new OAT\Property(property: 'slug', type: 'string', example: 'bar-name-1', description: 'Unique string that can be used to reference a specific bar.'),
        new OAT\Property(property: 'name', type: 'string', example: 'Bar name', description: 'Name of the bar'),
        new OAT\Property(property: 'subtitle', type: 'string', nullable: true, example: 'A short subtitle of a bar', description: 'Optional short quip about the bar'),
        new OAT\Property(property: 'description', type: 'string', nullable: true, example: 'Bar description', description: 'Description of the bar'),
        new OAT\Property(property: 'invite_code', type: 'string', nullable: true, example: '01H8S3VH2HTEB3D893AW8NTBBC', description: 'Random code used to invite people to the bar'),
        new OAT\Property(property: 'status', type: BarStatusEnum::class, example: 'active', description: 'Current status of the bar'),
        new OAT\Property(property: 'settings', type: BarSettings::class, description: 'Settings for the bar'),
        new OAT\Property(property: 'search_host', type: 'string', nullable: true, example: 'my.test.com', description: "Host URL used to access the bar's search engine"),
        new OAT\Property(property: 'search_token', type: 'string', nullable: true, example: null, description: "Auth token used to access the bar's search engine"),
        new OAT\Property(property: 'created_at', type: 'string', format: 'date-time', description: "Date and time when the bar was created"),
        new OAT\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true, description: "Date and time when the bar was last updated"),
        new OAT\Property(property: 'created_user', type: UserBasicResource::class, description: "User who created the bar"),
        new OAT\Property(property: 'updated_user', nullable: true, type: UserBasicResource::class, description: "User who last updated the bar"),
        new OAT\Property(property: 'access', type: 'object', properties: [
            new OAT\Property(type: 'integer', property: 'role_id', example: 1),
            new OAT\Property(type: 'boolean', property: 'can_edit', example: true),
            new OAT\Property(type: 'boolean', property: 'can_delete', example: true),
            new OAT\Property(type: 'boolean', property: 'can_activate', example: true),
            new OAT\Property(type: 'boolean', property: 'can_deactivate', example: true),
        ], description: 'User access rights for the bar', required: [
            'role_id',
            'can_edit',
            'can_delete',
            'can_activate',
            'can_deactivate',
        ]),
        new OAT\Property(property: 'images', type: 'array', items: new OAT\Items(type: ImageResource::class), description: 'Images associated with the bar'),
        new OAT\Property(property: 'is_public', type: 'boolean', default: false, example: true),
    ],
    required: [
        'id',
        'slug',
        'name',
        'subtitle',
        'description',
        'invite_code',
        'status',
        'settings',
        'search_host',
        'search_token',
        'created_at',
        'updated_at',
        'access',
        'is_public',
    ]
)]
class BarResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'subtitle' => $this->subtitle,
            'description' => $this->description,
            'invite_code' => $this->invite_code,
            'status' => $this->getStatus()->value,
            'settings' => $this->settings ?? [],
            'search_host' => config('scout.meilisearch.host'),
            'search_token' => $this->search_token,
            'created_at' => $this->created_at->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString() ?? null,
            'created_user' => new UserBasicResource($this->whenLoaded('createdUser')),
            'updated_user' => new UserBasicResource($this->whenLoaded('updatedUser')),
            'access' => [
                'role_id' => $this->memberships->where('user_id', $request->user()->id)->first()->user_role_id,
                'can_edit' => $request->user()->can('edit', $this->resource),
                'can_delete' => $request->user()->can('delete', $this->resource),
                'can_activate' => $request->user()->can('activate', $this->resource),
                'can_deactivate' => $request->user()->can('deactivate', $this->resource),
            ],
            'images' => $this->when(
                $this->relationLoaded('images'),
                fn () => ImageResource::collection($this->images)
            ),
            'is_public' => (bool) $this->is_public,
        ];
    }
}
