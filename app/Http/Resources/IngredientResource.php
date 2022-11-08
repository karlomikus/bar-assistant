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
            'ingredient_category_id' => $this->ingredient_category_id,
            'color' => $this->color,
            'category' => new IngredientCategoryResource($this->category),
            'cocktails_count' => $this->whenCounted('cocktails'),
            'varieties' => $this->when($this->relationLoaded('varieties') || $this->relationLoaded('parentIngredient'), function () {
                return $this->getAllRelatedIngredients()->map(function ($v) {
                    return [
                        'id' => $v->id,
                        'slug' => $v->slug,
                        'name' => $v->name,
                    ];
                })->toArray();
            }),
            'cocktails' => $this->whenLoaded('cocktails', function () {
                return $this->cocktails->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'slug' => $c->slug,
                        'name' => $c->name,
                    ];
                });
            })
        ];
    }
}
