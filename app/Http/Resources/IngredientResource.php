<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Ingredient
 */
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
            'slug' => $this->slug,
            'name' => $this->name,
            'strength' => $this->strength,
            'description' => $this->description,
            'origin' => $this->origin,
            'image_url' => $this->getImageUrl(),
            'parent_ingredient_id' => $this->parent_ingredient_id,
            'ingredient_category_id' => $this->ingredient_category_id,
            'color' => $this->color,
            'category' => new IngredientCategoryResource($this->category),
            'cocktails_count' => $this->whenCounted('cocktails'),
            'cocktails' => $this->whenLoaded('cocktails', function () {
                return $this->cocktails->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'name' => $c->name,
                        'slug' => $c->slug,
                    ];
                });
            })
        ];
    }
}
