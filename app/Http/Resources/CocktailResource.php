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
        $loadNavigation = (bool) $request->get('navigation', false);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'instructions' => e($this->instructions),
            'garnish' => e($this->garnish),
            'description' => e($this->description),
            'source' => $this->source,
            'public_id' => $this->public_id,
            'public_at' => $this->public_at?->toJson() ?? null,
            'main_image_id' => $this->images->sortBy('sort')->first()->id ?? null,
            'images' => ImageResource::collection($this->images),
            'tags' => $this->tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                ];
            }),
            'user_rating' => $this->user_rating ?? null,
            'average_rating' => (int) round($this->average_rating ?? 0),
            'glass' => new GlassResource($this->whenLoaded('glass')),
            'utensils' => UtensilResource::collection($this->whenLoaded('utensils')),
            'ingredients' => CocktailIngredientResource::collection($this->whenLoaded('ingredients')),
            'created_at' => $this->created_at->toJson(),
            'updated_at' => $this->updated_at->toJson(),
            'method' => new CocktailMethodResource($this->whenLoaded('method')),
            'collections' => CocktailCollectionResource::collection($this->whenLoaded('collections')),
            'abv' => $this->abv,
            'created_user' => new UserBasicResource($this->whenLoaded('createdUser')),
            'updated_user' => new UserBasicResource($this->whenLoaded('updatedUser')),
            'navigation' => $this->when($loadNavigation, function () {
                return [
                    'prev' => $this->getPrevSlug(),
                    'next' => $this->getNextSlug(),
                ];
            })
        ];
    }
}
