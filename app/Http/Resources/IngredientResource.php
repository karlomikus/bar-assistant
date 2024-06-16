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
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'strength' => $this->strength,
            'description' => e($this->description),
            'origin' => $this->origin,
            'main_image_id' => $this->images->first()->id ?? null,
            'created_at' => $this->created_at->toJson(),
            'updated_at' => $this->updated_at?->toJson(),
            'images' => ImageResource::collection($this->images),
            'parent_ingredient' => $this->when($this->relationLoaded('parentIngredient') && $this->parent_ingredient_id !== null, function () {
                return [
                    'id' => $this->parentIngredient->id,
                    'slug' => $this->parentIngredient->slug,
                    'name' => $this->parentIngredient->name,
                ];
            }),
            'color' => $this->color,
            'category' => new IngredientCategoryResource($this->category),
            'cocktails_count' => $this->whenCounted('cocktails'),
            'varieties' => $this->when($this->relationLoaded('varieties') && $this->relationLoaded('parentIngredient'), function () {
                return $this->getAllRelatedIngredients()->map(function ($v) {
                    return [
                        'id' => $v->id,
                        'slug' => $v->slug,
                        'name' => $v->name,
                    ];
                })->toArray();
            }),
            'cocktails' => $this->when($this->relationLoaded('cocktails') || $this->relationLoaded('cocktailIngredientSubstitutes'), function () {
                return $this->cocktails->merge($this->cocktailsAsSubstituteIngredient())->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'slug' => $c->slug,
                        'name' => $c->name,
                    ];
                })->sortBy('name')->toArray();
            }),
            'created_user' => new UserBasicResource($this->whenLoaded('createdUser')),
            'updated_user' => new UserBasicResource($this->whenLoaded('updatedUser')),
            'access' => $this->when($this->relationLoaded('createdUser'), fn () => [
                'can_edit' => $request->user()->can('edit', $this->resource),
                'can_delete' => $request->user()->can('delete', $this->resource),
            ]),
            'ingredient_parts' => $this->when($this->relationLoaded('ingredientParts'), fn () => $this->ingredientParts->map(fn ($cip) => [
                'id' => $cip->ingredient_id,
                'name' => $cip->ingredient->name,
                'slug' => $cip->ingredient->slug,
            ])),
        ];
    }
}
