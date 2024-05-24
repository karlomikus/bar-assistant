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
        // TODO: Rename all this...
        $isSingleCocktail = (bool) $request->get('navigation', false);

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
            'main_image_id' => $this->images->sortBy('sort')->first()->id ?? null, // deprecate
            'images' => ImageResource::collection($this->images),
            'tags' => $this->tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                ];
            }),
            'rating' => [
                'user' => $this->user_rating ?? null,
                'average' => (int) round($this->average_rating ?? 0),
                'total_votes' => $this->totalRatedCount(),
            ],
            'glass' => new GlassResource($this->whenLoaded('glass')),
            'utensils' => UtensilResource::collection($this->whenLoaded('utensils')),
            'ingredients' => CocktailIngredientResource::collection($this->whenLoaded('ingredients')),
            'created_at' => $this->created_at->toJson(),
            'updated_at' => $this->updated_at?->toJson(),
            'method' => new CocktailMethodResource($this->whenLoaded('method')),
            'abv' => $this->abv,
            'volume_ml' => $this->when($this->relationLoaded('ingredients'), fn () => $this->getVolume()),
            'alcohol_units' => $this->when($this->relationLoaded('method'), fn () => $this->getAlcoholUnits()),
            'calories' => $this->when($this->relationLoaded('method'), fn () => $this->getCalories()),
            'created_user' => new UserBasicResource($this->whenLoaded('createdUser')),
            'updated_user' => new UserBasicResource($this->whenLoaded('updatedUser')),
            'in_shelf' => $this->when($isSingleCocktail, fn () => $this->canUserMake(auth()->user())),
            'access' => $this->when($isSingleCocktail, function () use ($request) {
                return [
                    'can_edit' => $request->user()->can('edit', $this->resource),
                    'can_delete' => $request->user()->can('delete', $this->resource),
                    'can_rate' => $request->user()->can('rate', $this->resource),
                    'can_add_note' => $request->user()->can('addNote', $this->resource),
                ];
            }),
            'navigation' => $this->when($isSingleCocktail, function () {
                return [
                    'prev' => $this->getPrevCocktail()?->slug,
                    'next' => $this->getNextCocktail()?->slug,
                ];
            })
        ];
    }
}
