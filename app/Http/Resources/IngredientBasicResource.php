<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Ingredient
 */
#[OAT\Schema(
    schema: 'IngredientBasic',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1, description: 'The ID of the ingredient'),
        new OAT\Property(property: 'slug', type: 'string', example: 'gin-1', description: 'The slug of the ingredient'),
        new OAT\Property(property: 'name', type: 'string', example: 'Gin', description: 'The name of the ingredient'),
        new OAT\Property(property: 'image', type: ImageResource::class, description: 'Main resource image'),
    ],
    description: 'Minimal ingredient information',
    required: ['id', 'slug', 'name']
)]
class IngredientBasicResource extends JsonResource
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
            'image' => $this->when($this->relationLoaded('images'), fn () => new ImageResource($this->getMainImage())),
        ];
    }
}
