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
            'public_id' => $this->public_id,
            'public_at' => $this->public_at?->toAtomString() ?? null,
            'images' => $this->when(
                $this->relationLoaded('images'),
                fn () => ImageResource::collection($this->images)
            ),
            'tags' => $this->when(
                $this->relationLoaded('tags'),
                fn () => $this->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ];
                })
            ),
            'rating' => $this->when(
                $this->relationLoaded('ratings'),
                fn () => [
                    'user' => $this->user_rating ?? null,
                    'average' => (int) round($this->average_rating ?? 0),
                    'total_votes' => $this->totalRatedCount(),
                ]
            ),
            'glass' => new GlassResource($this->whenLoaded('glass')),
            'utensils' => UtensilResource::collection($this->whenLoaded('utensils')),
            'ingredients' => CocktailIngredientResource::collection($this->whenLoaded('ingredients')),
            'created_at' => $this->created_at->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
            'method' => new CocktailMethodResource($this->whenLoaded('method')),
            'abv' => $this->abv,
            'volume_ml' => $this->when($this->relationLoaded('ingredients'), fn () => $this->getVolume()),
            'alcohol_units' => $this->when($this->relationLoaded('method'), fn () => $this->getAlcoholUnits()),
            'calories' => $this->when($this->relationLoaded('method'), fn () => $this->getCalories()),
            'created_user' => new UserBasicResource($this->whenLoaded('createdUser')),
            'updated_user' => new UserBasicResource($this->whenLoaded('updatedUser')),
            'in_shelf' => in_array($this->id, $request->user()->getShelfCocktailsOnce($this->bar_id)),
            // 'in_shelf' => $this->when($this->relationLoaded('ingredients'), fn () => $this->inUserShelf($request->user())),
            'in_bar_shelf' => in_array($this->id, $this->bar->getShelfCocktailsOnce()),
            // 'in_bar_shelf' => $this->when($this->relationLoaded('ingredients'), fn () => $this->inBarShelf()),
            'is_favorited' => $request->user()->getBarMembership($this->bar_id)->cocktailFavorites->where('cocktail_id', $this->id)->isNotEmpty(),
            'access' => $this->when(true, function () use ($request) {
                return [
                    'can_edit' => $request->user()->can('edit', $this->resource),
                    'can_delete' => $request->user()->can('delete', $this->resource),
                    'can_rate' => $request->user()->can('rate', $this->resource),
                    'can_add_note' => $request->user()->can('addNote', $this->resource),
                ];
            }),
            // 'navigation' => $this->when(true, function () {
            //     return [
            //         'prev' => new CocktailBasicResource($this->getPrevCocktail()),
            //         'next' => new CocktailBasicResource($this->getNextCocktail()),
            //     ];
            // })
        ];
    }
}
