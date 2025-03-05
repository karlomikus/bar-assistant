<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\User
 */
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
