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
            'created_at' => $this->created_at->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
            'images' => $this->when(
                $this->relationLoaded('images'),
                fn () => ImageResource::collection($this->images)
            ),
            'parent_ingredient' => $this->when($this->relationLoaded('parentIngredient') && $this->parent_ingredient_id !== null, function () {
                return new IngredientBasicResource($this->parentIngredient);
            }),
            'color' => $this->color,
            'category' => new IngredientCategoryResource($this->whenLoaded('category')),
            'cocktails_count' => $this->whenCounted('cocktails'),
            'cocktails_as_substitute_count' => $this->when(
                $this->relationLoaded('cocktailIngredientSubstitutes'),
                fn () => $this->cocktailsAsSubstituteIngredient()->count()
            ),
            'varieties' => $this->when($this->relationLoaded('varieties') && $this->relationLoaded('parentIngredient'), function () {
                return IngredientBasicResource::collection($this->getAllRelatedIngredients());
            }),
            'created_user' => new UserBasicResource($this->whenLoaded('createdUser')),
            'updated_user' => new UserBasicResource($this->whenLoaded('updatedUser')),
            'access' => $this->when(true, fn () => [
                'can_edit' => $request->user()->can('edit', $this->resource),
                'can_delete' => $request->user()->can('delete', $this->resource),
            ]),
            'in_shelf' => $this->userHasInShelf($request->user()),
            'in_shopping_list' => $this->userHasInShoppingList($request->user()),
            'used_as_substitute_for' => $this->when(
                $this->relationLoaded('cocktailIngredientSubstitutes'),
                fn () => IngredientBasicResource::collection($this->getIngredientsUsedAsSubstituteFor())
            ),
            'can_be_substituted_with' => $this->when(
                $this->relationLoaded('cocktailIngredientSubstitutes'),
                fn () => IngredientBasicResource::collection($this->getCanBeSubstitutedWithIngredients())
            ),
            'ingredient_parts' => $this->when(
                $this->relationLoaded('ingredientParts'),
                fn () => $this->ingredientParts->map(fn ($cip) => new IngredientBasicResource($cip->ingredient))
            ),
            'prices' => $this->when($this->relationLoaded('prices'), function () {
                return IngredientPriceResource::collection($this->prices);
            }),
        ];
    }
}
