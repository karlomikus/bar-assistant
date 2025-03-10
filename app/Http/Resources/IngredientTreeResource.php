<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Ingredient
 */
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
