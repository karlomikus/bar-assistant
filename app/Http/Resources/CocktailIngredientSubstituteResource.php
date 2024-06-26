<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\CocktailIngredientSubstitute
 */
class CocktailIngredientSubstituteResource extends JsonResource
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
            'id' => $this->ingredient_id,
            'slug' => $this->ingredient->slug,
            'name' => $this->ingredient->name,
            'amount' => $this->amount,
            'amount_max' => $this->amount_max,
            'units' => $this->units,
            'in_shelf' => $this->userHasInShelf($request->user()),
        ];
    }
}
