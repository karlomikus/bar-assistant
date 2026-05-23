<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\BarMembership
 */
#[OAT\Schema(
    schema: 'User',
    description: 'Represents a user in current bar',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1, description: 'User ID'),
        new OAT\Property(property: 'name', type: 'string', example: 'Bartender', description: 'User name'),
        new OAT\Property(property: 'email', type: 'string', example: 'test@email.com', description: 'User email'),
        new OAT\Property(property: 'is_subscribed', type: 'boolean', example: true, description: 'Subscription status'),
        new OAT\Property(property: 'role', type: 'object', properties: [
            new OAT\Property(property: 'id', type: 'integer', nullable: true, example: 1, description: 'Role ID'),
            new OAT\Property(property: 'name', type: 'string', nullable: true, example: 'Admin', description: 'Role name'),
        ], required: [
            'id',
            'name',
        ]),
    ],
    required: [
        'id',
        'name',
        'email',
        'is_subscribed',
        'role',
    ],
)]
class MemberResource extends JsonResource
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
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'is_subscribed' => $this->user->hasActiveSubscription(),
            'role' => [
                'id' => $this->role->id,
                'name' => $this->role->name,
            ],
        ];
    }
}
