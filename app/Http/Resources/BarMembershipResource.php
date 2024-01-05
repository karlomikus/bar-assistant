<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\BarMembership
 */
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
            'use_parent_as_substitute' => $this->use_parent_as_substitute,
        ];
    }
}
