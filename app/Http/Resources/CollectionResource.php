<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Collection
 */
class CollectionResource extends JsonResource
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
            'description' => $this->description,
            'is_bar_shared' => $this->is_bar_shared,
            'created_at' => $this->created_at->toAtomString(),
            'created_user' => $this->whenLoaded('barMembership', function () {
                return new UserBasicResource($this->barMembership->user);
            }),
            'cocktails' => CocktailBasicResource::collection($this->whenLoaded('cocktails')),
        ];
    }
}
