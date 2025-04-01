<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\OpenAPI\Schemas\ProfileSettings;

/**
 * @mixin \Kami\Cocktail\Models\User
 */
#[OAT\Schema(
    schema: 'Profile',
    description: 'User profile resource',
    properties: [
        new OAT\Property(property: 'id', type: 'string', example: 1),
        new OAT\Property(property: 'name', type: 'string', example: 'Floral'),
        new OAT\Property(property: 'email', type: 'string', example: 'example@example.com', description: 'User email'),
        new OAT\Property(property: 'is_subscribed', type: 'boolean', example: true, description: 'Is user subscribed'),
        new OAT\Property(property: 'memberships', type: 'array', items: new OAT\Items(type: BarMembershipResource::class), description: 'User memberships'),
        new OAT\Property(property: 'oauth_credentials', type: 'array', description: 'OAuth credentials', items: new OAT\Items(type: OauthCredentialResource::class)),
        new OAT\Property(property: 'settings', type: ProfileSettings::class),
    ],
    required: ['id', 'name', 'email', 'is_subscribed', 'memberships', 'oauth_credentials', 'settings']
)]
class ProfileResource extends JsonResource
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
            'email' => $this->email,
            'is_subscribed' => $this->hasActiveSubscription(),
            'memberships' => BarMembershipResource::collection($this->memberships),
            'oauth_credentials' => OauthCredentialResource::collection($this->oauthCredentials),
            'settings' => $this->settings?->toArray(),
        ];
    }
}
