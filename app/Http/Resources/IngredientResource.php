<?php

namespace Kami\Cocktail\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class IngredientResource extends JsonResource
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
            'strength' => $this->strength,
            'description' => $this->description,
            'origin' => $this->origin,
            'image' => $this->image,
            'category' => new IngredientCategoryResource($this->category),
            'cocktails' => $this->cocktails->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                ];
            })
        ];
    }
}
