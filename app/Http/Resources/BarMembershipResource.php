<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\BarMembership
 */
#[OAT\Schema(
    schema: 'BarMembership',
    description: 'Represents a bar membership',
    properties: [
        new OAT\Property(property: 'user_id', type: 'integer', example: 1, description: 'The ID of the user'),
        new OAT\Property(property: 'user_name', type: 'string', example: 'Bartender', description: 'The name of the user'),
        new OAT\Property(property: 'bar_id', type: 'integer', example: 1, description: 'The ID of the bar'),
        new OAT\Property(property: 'is_shelf_public', type: 'boolean', example: true, description: 'Indicates if the shelf is public'),
    ],
    required: ['user_id', 'user_name', 'bar_id', 'is_shelf_public']
)]
class BarMembershipResource extends JsonResource
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
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'bar_id' => $this->bar_id,
            'is_shelf_public' => $this->is_shelf_public,
        ];
    }
}
