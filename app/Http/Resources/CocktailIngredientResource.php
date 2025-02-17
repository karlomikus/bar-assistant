<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\CocktailIngredient
 */
class CocktailIngredientResource extends JsonResource
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
            'sort' => $this->sort,
            'amount' => $this->amount,
            'amount_max' => $this->amount_max,
            'units' => $this->units,
            'optional' => (bool) $this->optional,
            'ingredient' => new IngredientBasicResource($this->ingredient),
            'substitutes' => CocktailIngredientSubstituteResource::collection($this->whenLoaded('substitutes')),
            'variants_in_shelf' => $this->when($this->ingredient->hasLoadedDescendants(), fn () => IngredientBasicResource::collection($this->ingredient->barShelfVariants())),
            'note' => $this->note,
            'is_specified' => $this->is_specified,
            'formatted' => new AmountFormats($this->resource),
            'in_shelf' => $this->when(
                $this->relationLoaded('ingredient'),
                fn () => $this->ingredient->userHasInShelf($request->user())
            ),
            'in_shelf_as_variant' => $this->when(
                $this->ingredient->hasLoadedDescendants(),
                fn () => !$this->is_specified && $this->ingredient->userShelfVariants($request->user())->count() > 0
            ),
            'in_shelf_as_substitute' => $this->when(
                $this->relationLoaded('substitutes'),
                fn () => $this->userHasInShelfAsSubstitute($request->user())
            ),
            'in_shelf_as_complex_ingredient' => $this->when(
                $this->ingredient->relationLoaded('ingredientParts'),
                fn () => $this->ingredient->userHasInShelfAsComplexIngredient($request->user())
            ),
            'in_bar_shelf' => $this->when(
                $this->relationLoaded('ingredient'),
                fn () => $this->ingredient->barHasInShelf()
            ),
            'in_bar_shelf_as_substitute' => $this->when(
                $this->relationLoaded('substitutes'),
                fn () => $this->barHasInShelfAsSubstitute()
            ),
            'in_bar_shelf_as_complex_ingredient' => $this->when(
                $this->ingredient->relationLoaded('ingredientParts'),
                fn () => $this->ingredient->barHasInShelfAsComplexIngredient()
            ),
            'in_bar_shelf_as_variant' => $this->when(
                $this->ingredient->hasLoadedDescendants(),
                fn () => !$this->is_specified && $this->ingredient->barShelfVariants()->count() > 0
            ),
        ];
    }
}
