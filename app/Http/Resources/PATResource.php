<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\PersonalAccessToken
 */
#[OAT\Schema(
    schema: 'PersonalAccessToken',
    description: 'Personal Access Token',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1),
        new OAT\Property(property: 'name', type: 'string', example: 'user_generated'),
        new OAT\Property(property: 'abilities', type: 'array', items: new OAT\Items(type: 'string'), example: ['cocktails.read', 'cocktails.write', 'ingredients.read', 'ingredients.write']),
        new OAT\Property(property: 'last_used_at', type: 'string', format: 'date-time', example: '2023-05-14T21:23:40.000000Z'),
        new OAT\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2023-05-14T21:23:40.000000Z'),
        new OAT\Property(property: 'expires_at', type: 'string', format: 'date-time', example: '2023-05-14T21:23:40.000000Z'),
    ],
    required: ['id', 'name', 'abilities', 'last_used_at', 'created_at', 'expires_at'],
)]
class PATResource extends JsonResource
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
            'name' => $this->name,
            'abilities' => $this->abilities,
            'last_used_at' => $this->last_used_at,
            'created_at' => $this->created_at,
            'expires_at' => $this->expires_at,
        ];
    }
}
