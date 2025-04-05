<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Ingredient
 */
#[OAT\Schema(
    schema: 'IngredientTree',
    description: 'Represents an ingredient tree with its children',
    properties: [
        new OAT\Property(property: 'ingredient', type: IngredientBasicResource::class),
        new OAT\Property(
            property: 'children',
            type: 'array',
            description: 'Recursive list of child ingredients',
            items: new OAT\Items(ref: self::class)
        ),
    ],
    required: ['ingredient', 'children']
)]
class IngredientTreeResource extends JsonResource
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
            'ingredient' => new IngredientBasicResource($this),
            'children' => $this->allChildren->isNotEmpty() ? self::collection($this->allChildren) : [],
        ];
    }
}
