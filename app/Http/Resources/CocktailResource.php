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
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'instructions' => $this->instructions,
            'garnish' => $this->garnish,
            'description' => $this->description,
            'source' => $this->source,
            'image_copyright' => $this->images->first()->copyright,
            'image_url' => $this->getImageUrl(),
            'tags' => $this->tags->pluck('name'),
            'short_ingredients' => $this->ingredients->pluck('ingredient.name'),
            'ingredients' => CocktailIngredientResource::collection($this->ingredients),
        ];
    }
}
