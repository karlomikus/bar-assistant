<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

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
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'instructions' => e($this->instructions),
            'garnish' => e($this->garnish),
            'description' => e($this->description),
            'source' => $this->source,
            'main_image_id' => $this->images->first()->id ?? null,
            'images' => ImageResource::collection($this->images),
            'tags' => $this->tags->pluck('name'),
            'user_id' => $this->user_id,
            'user_rating' => $this->getUserRating($request->user()->id)?->rating ?? null,
            'average_rating' => $this->getAverageRating(),
            'glass' => new GlassResource($this->whenLoaded('glass')),
            'short_ingredients' => $this->ingredients->pluck('ingredient.name'),
            'ingredients' => CocktailIngredientResource::collection($this->ingredients),
            'main_ingredient_name' => $this->getMainIngredient()?->ingredient->name ?? null,
            'created_at' => $this->created_at->toDateTimeString(),
            'method' => new CocktailMethodResource($this->whenLoaded('method')),
            'abv' => $this->getABV(),
        ];
    }
}
