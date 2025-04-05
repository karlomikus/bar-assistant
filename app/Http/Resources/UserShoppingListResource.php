<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\UserShoppingList
 */
#[OAT\Schema(
    schema: 'ShoppingList',
    description: 'Shopping list resource',
    properties: [
        new OAT\Property(property: 'ingredient', type: IngredientBasicResource::class),
        new OAT\Property(property: 'quantity', type: 'integer', nullable: true, example: 3),
    ],
    required: ['ingredient', 'quantity']
)]
class UserShoppingListResource extends JsonResource
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
            'quantity' => $this->quantity,
        ];
    }
}
