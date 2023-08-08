<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

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
            'name' => $this->name,
            'instructions' => e($this->instructions),
            'garnish' => e($this->garnish),
            'description' => e($this->description),
            'source' => $this->source,
            'tags' => $this->tags->pluck('name'),
            'glass' => $this->glass->name ?? null,
            'ustensils' => $this->ustensils->pluck('name'),
            'method' => $this->method->name ?? null,
            'main_image_id' => $this->images->sortBy('sort')->first()->id ?? null,
            'images' => ImageResource::collection($this->images),
            'abv' => $this->abv,
            'ingredients' => $this->ingredients->map(function ($cocktailIngredient) {
                return [
                    'name' => $cocktailIngredient->ingredient->name,
                    'amount' => $cocktailIngredient->amount,
                    'units' => $cocktailIngredient->units,
                    'optional' => (bool) $cocktailIngredient->optional,
                    'substitutes' => $cocktailIngredient->substitutes->pluck('ingredient.name')
                ];
            }),
        ];
    }
}
