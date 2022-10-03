<?php

namespace Kami\Cocktail\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CocktailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'instructions' => $this->instructions,
            'description' => $this->description,
            'source' => $this->source,
            'image' => $this->image,
            'tags' => $this->tags->pluck('name'),
            'short_ingredients' => $this->ingredients->pluck('ingredient.name'),
        ];
    }
}
