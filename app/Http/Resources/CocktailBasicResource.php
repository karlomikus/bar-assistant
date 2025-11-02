<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Cocktail
 */
#[OAT\Schema(
    schema: 'CocktailBasic',
    description: 'Minimal cocktail information',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1, description: 'The ID of the cocktail'),
        new OAT\Property(property: 'slug', type: 'string', example: 'old-fashioned-1', description: 'The slug of the cocktail'),
        new OAT\Property(property: 'name', type: 'string', example: 'Old fashioned', description: 'The name of the cocktail'),
        new OAT\Property(property: 'short_ingredients', type: 'array', items: new OAT\Items(type: 'string', example: 'Vodka'), description: 'List of short ingredient names'),
        new OAT\Property(property: 'image', type: ImageResource::class),
    ],
    required: ['id', 'slug', 'name']
)]
class CocktailBasicResource extends JsonResource
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
            'slug' => $this->slug,
            'name' => $this->name,
            'short_ingredients' => $this->getIngredientNames(),
            'image' => $this->when($this->relationLoaded('images'), fn () => new ImageResource($this->getMainImage())),
        ];
    }
}
