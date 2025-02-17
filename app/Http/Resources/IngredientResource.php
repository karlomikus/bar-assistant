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
            'materialized_path' => $this->materialized_path,
            'hierarchy' => [
                'path_to_self' => $this->when($this->whenLoaded('ancestors'), fn () => $this->getAncestors()->pluck('name')->implode(' > ')),
                'parent_ingredient' => $this->whenLoaded('parentIngredient', fn () => new IngredientBasicResource($this->parentIngredient)),
                'descendants' => IngredientBasicResource::collection($this->getDescendants()),
                'ancestors' => IngredientBasicResource::collection($this->getAncestors()),
            ],
            'images' => $this->when(
                $this->relationLoaded('images'),
                fn () => ImageResource::collection($this->images)
            ),
            'color' => $this->color,
            'cocktails_count' => $this->whenCounted('cocktails'),
            'cocktails_as_substitute_count' => $this->when(
                $this->relationLoaded('cocktailIngredientSubstitutes'),
                fn () => $this->cocktailsAsSubstituteIngredient()->count()
            ),
            'created_user' => new UserBasicResource($this->whenLoaded('createdUser')),
            'updated_user' => new UserBasicResource($this->whenLoaded('updatedUser')),
            'access' => $this->when(true, fn () => [
                'can_edit' => $request->user()->can('edit', $this->resource),
                'can_delete' => $request->user()->can('delete', $this->resource),
            ]),
            'in_shelf' => $this->userHasInShelf($request->user()),
            'in_shelf_as_variant' => $this->userShelfVariants($request->user())->count() > 0,
            'in_bar_shelf' => $this->barHasInShelf(),
            'in_bar_shelf_as_variant' => $this->barShelfVariants()->count() > 0,
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
            'calculator_id' => $this->calculator_id,
        ];
    }
}
