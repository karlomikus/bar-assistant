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
            'substitutes' => $this->when($this->relationLoaded('substitutes'), fn () => CocktailIngredientSubstituteResource::collection($this->getPossibleSubstitutes())),
            'note' => $this->note,
            'formatted' => new AmountFormats($this->resource),
            'in_shelf' => $this->when($this->relationLoaded('substitutes'), fn () => $this->userHasInShelf($request->user())),
            'in_shelf_as_substitute' => $this->when($this->relationLoaded('substitutes'), fn () => $this->userHasInShelfAsSubstitute($request->user())),
            'in_shelf_as_complex_ingredient' => $this->when($this->relationLoaded('substitutes'), fn () => $this->userHasInShelfAsComplexIngredient($request->user())),
            'in_bar_shelf' => $this->when($this->relationLoaded('substitutes'), fn () => $this->barHasInShelf()),
            'in_bar_shelf_as_substitute' => $this->when($this->relationLoaded('substitutes'), fn () => $this->barHasInShelfAsSubstitute()),
            'in_bar_shelf_as_variant' => $this->when($this->relationLoaded('substitutes'), fn () => $this->barHasVariantInShelf()),
            'in_bar_shelf_as_complex_ingredient' => $this->when($this->relationLoaded('substitutes'), fn () => $this->barHasInShelfAsComplexIngredient()),
        ];
    }
}
