<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\BarMembership
 */
#[OAT\Schema(
    schema: 'BarMember',
    description: 'Represents a bar member with minimal, privacy-safe fields',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1, description: 'User ID'),
        new OAT\Property(property: 'name', type: 'string', example: 'Bartender', description: 'User name'),
    ],
    required: ['id', 'name']
)]
class BarMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray($request)
    {
        return [
            'id' => $this->user?->id,
            'name' => $this->user?->name,
        ];
    }
}
