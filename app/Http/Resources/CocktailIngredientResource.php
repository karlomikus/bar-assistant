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
            'ingredient_id' => $this->ingredient_id,
            'name' => $this->ingredient->name,
            'ingredient_slug' => $this->ingredient->slug,
            'substitutes' => CocktailIngredientSubstituteResource::collection($this->whenLoaded('substitutes')),
            'note' => $this->note,
            'formatted' => new AmountFormats($this->resource)
        ];
    }
}
