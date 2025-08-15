<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources\Public;

use Kami\Cocktail\Models\CocktailIngredient;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\AmountFormats;
use Kami\Cocktail\Models\CocktailIngredientSubstitute;

/**
 * @mixin \Kami\Cocktail\Models\Cocktail
 */
class CocktailResource extends JsonResource
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
            'slug' => $this->slug,
            'name' => $this->name,
            'instructions' => e($this->instructions),
            'garnish' => e($this->garnish),
            'description' => e($this->description),
            'source' => $this->source,
            'public_id' => $this->public_id,
            'public_at' => $this->public_at?->toAtomString() ?? null,
            'images' => ImageResource::collection($this->images),
            'tags' => $this->when(
                $this->relationLoaded('tags'),
                fn () => $this->tags->map(function ($tag) {
                    return $tag->name;
                })
            ),
            'glass' => $this->glass->name ?? null,
            'utensils' => $this->utensils->pluck('name'),
            'method' => $this->method->name ?? null,
            'created_at' => $this->created_at->toAtomString(),
            'abv' => $this->abv,
            // 'volume_ml' => $this->when($this->relationLoaded('ingredients'), fn () => $this->getVolume()),
            // 'alcohol_units' => $this->when($this->relationLoaded('method'), fn () => $this->getAlcoholUnits()),
            // 'calories' => $this->when($this->relationLoaded('method'), fn () => $this->getCalories()),
            'year' => $this->year,
            'ingredients' => $this->ingredients->map(function (CocktailIngredient $cocktailIngredient) {
                return [
                    'name' => $cocktailIngredient->ingredient->name,
                    'amount' => $cocktailIngredient->amount,
                    'amount_max' => $cocktailIngredient->amount_max,
                    'units' => $cocktailIngredient->units,
                    'units_formatted' => new AmountFormats($cocktailIngredient),
                    'optional' => (bool) $cocktailIngredient->optional,
                    'note' => $cocktailIngredient->note,
                    'substitutes' => $cocktailIngredient->substitutes->map(function (CocktailIngredientSubstitute $substitute) {
                        return [
                            'name' => $substitute->ingredient->name,
                            'amount' => $substitute->amount,
                            'amount_max' => $substitute->amount_max,
                            'units' => $substitute->units,
                        ];
                    })->toArray(),
                ];
            }),
        ];
    }
}
