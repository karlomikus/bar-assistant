<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Kami\Cocktail\Models\BarMembership;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\User
 */
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
            'role' => $this->memberships->where('bar_id', $bar->id)->map(function (BarMembership $membership) {
                return [
                    'bar_id' => $membership->bar_id,
                    'role_id' => $membership->role->id ?? null,
                    'role_name' => $membership->role->name ?? null,
                ];
            })->first(),
        ];
    }
}
