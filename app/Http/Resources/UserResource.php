<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\BarMembership;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\User
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
            new OAT\Property(property: 'bar_id', type: 'integer', example: 1, description: 'Bar ID'),
            new OAT\Property(property: 'role_id', type: 'integer', nullable: true, example: 1, description: 'Role ID'),
            new OAT\Property(property: 'role_name', type: 'string', nullable: true, example: 'Admin', description: 'Role name'),
        ], required: [
            'bar_id',
            'role_id',
            'role_name',
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
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $bar = bar();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_subscribed' => $this->hasActiveSubscription(),
            'role' => $this->memberships->where('bar_id', $bar->id)->map(fn(BarMembership $membership) => [
                'bar_id' => $membership->bar_id,
                'role_id' => $membership->role->id ?? null,
                'role_name' => $membership->role->name ?? null,
            ])->first(),
        ];
    }
}
