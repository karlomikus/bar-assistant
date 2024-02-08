<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\PersonalAccessToken
 */
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
            'test' => $this->test ?? null,
            'abilities' => $this->abilities,
            'last_used_at' => $this->last_used_at,
            'created_at' => $this->created_at,
            'expires_at' => $this->expires_at,
        ];
    }
}
