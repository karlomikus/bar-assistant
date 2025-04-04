<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\CocktailIngredientSubstitute
 */
#[OAT\Schema(
    schema: 'CocktailIngredientSubstitute',
    description: 'Cocktail ingredient substitute',
    properties: [
        new OAT\Property(property: 'ingredient', type: IngredientBasicResource::class),
        new OAT\Property(property: 'amount', type: 'number', example: 30, nullable: true),
        new OAT\Property(property: 'amount_max', type: 'number', example: 60, nullable: true),
        new OAT\Property(property: 'units', type: 'string', example: 'ml', nullable: true),
        new OAT\Property(property: 'in_shelf', type: 'boolean', example: true),
        new OAT\Property(property: 'in_bar_shelf', type: 'boolean', example: true),
    ],
    required: ['ingredient', 'amount', 'amount_max', 'units', 'in_shelf', 'in_bar_shelf']
)]
class CocktailIngredientSubstituteResource extends JsonResource
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
            'ingredient' => new IngredientBasicResource($this->ingredient),
            'amount' => $this->amount,
            'amount_max' => $this->amount_max,
            'units' => $this->units,
            'in_shelf' => $this->userHasInShelf($request->user()),
            'in_bar_shelf' => $this->barHasInShelf(),
        ];
    }
}
