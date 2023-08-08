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
            'has_public_link' => $this->public_id !== null,
            'public_id' => $this->public_id,
            'main_image_id' => $this->images->sortBy('sort')->first()->id ?? null,
            'images' => ImageResource::collection($this->images),
            'tags' => $this->tags->pluck('name'), // TODO: introduce braking change
            'cocktail_tags' => $this->tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                ];
            }),
            'user_rating' => $this->user_rating ?? null,
            'average_rating' => (int) round($this->average_rating ?? 0),
            'glass' => new GlassResource($this->whenLoaded('glass')),
            'utensils' => UtensilResource::collection($this->whenLoaded('utensils')),
            'short_ingredients' => $this->ingredients->pluck('ingredient.name'), // deprecate
            'ingredients' => CocktailIngredientResource::collection($this->ingredients), // TODO: Cond. load
            'created_at' => $this->created_at->toDateTimeString(),
            'method' => new CocktailMethodResource($this->whenLoaded('method')),
            'collections' => CocktailCollectionResource::collection($this->whenLoaded('collections')),
            'abv' => $this->abv,
            'notes' => NoteResource::collection($this->whenLoaded('notes')),
            'user' => new UserBasicResource($this->whenLoaded('user')),
        ];
    }
}
