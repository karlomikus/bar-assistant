<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\CocktailIngredient
 */
class CocktailIngredientResource extends JsonResource
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
            'sort' => $this->sort,
            'amount' => $this->amount,
            'units' => $this->units,
            'optional' => (bool) $this->optional,
            'ingredient_id' => $this->ingredient_id,
            'name' => $this->ingredient->name,
            'ingredient_slug' => $this->ingredient->slug,
        ];
    }
}
