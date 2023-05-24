<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Cocktail
 */
class CocktailShareResource extends JsonResource
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
            'glass' => $this->glass?->name ?? null,
            'method' => $this->method?->name ?? null,
            'images' => $this->images->map(function (\Kami\Cocktail\Models\Image $image) {
                return [
                    'url' => $image->getImageUrl(),
                    'copyright' => $image->copyright,
                    'sort' => $image->sort,
                ];
            }),
            'ingredients' => $this->ingredients->map(function (\Kami\Cocktail\Models\CocktailIngredient $cIngredient) {
                return [
                    'sort' => $cIngredient->sort,
                    'name' => $cIngredient->ingredient->name,
                    'amount' => $cIngredient->amount,
                    'units' => $cIngredient->units,
                    'optional' => (bool) $cIngredient->optional,
                    'category' => $cIngredient->ingredient->category->name,
                    'description' => $cIngredient->ingredient->description,
                    'strength' => $cIngredient->ingredient->strength,
                    'origin' => $cIngredient->ingredient->origin,
                    'substitutes' => $cIngredient->substitutes->pluck('name'),
                ];
            }),
        ];
    }
}
