<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Kami\Cocktail\Models\Image;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Cocktail
 */
class ExploreCocktailResource extends JsonResource
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
            'bar' => new BarBasicResource($this->bar),
            'name' => $this->name,
            'instructions' => e($this->instructions),
            'garnish' => e($this->garnish),
            'description' => e($this->description),
            'source' => $this->source,
            'tags' => $this->tags->pluck('name'),
            'glass' => $this->glass->name ?? null,
            'utensils' => $this->utensils->pluck('name'),
            'method' => $this->method->name ?? null,
            'images' => $this->images->map(function (Image $image) {
                return [
                    'sort' => $image->sort,
                    'placeholder_hash' => $image->placeholder_hash,
                    'url' => $image->getImageUrl(),
                    'copyright' => $image->copyright,
                ];
            }),
            'abv' => $this->abv,
            'ingredients' => $this->ingredients->map(function ($cocktailIngredient) {
                return [
                    'name' => $cocktailIngredient->ingredient->name,
                    'amount' => $cocktailIngredient->amount,
                    'amount_max' => $cocktailIngredient->amount_max,
                    'units' => $cocktailIngredient->units,
                    'optional' => (bool) $cocktailIngredient->optional,
                    'note' => $cocktailIngredient->note,
                    'substitutes' => $cocktailIngredient->substitutes->map(function ($substitute) {
                        return [
                            'name' => $substitute->ingredient->name,
                            'amount' => $substitute->amount,
                            'amount_max' => $substitute->amount_max,
                            'units' => $substitute->units,
                        ];
                    })
                ];
            }),
        ];
    }
}
