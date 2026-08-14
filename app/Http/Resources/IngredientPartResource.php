<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\ComplexIngredient
 */
#[OAT\Schema(
    schema: 'IngredientPart',
    description: 'Represents a part of a complex ingredient with amount',
    properties: [
        new OAT\Property(property: 'amount', type: 'number', format: 'float', example: 200.0, description: 'The amount of this part'),
        new OAT\Property(property: 'amount_max', type: 'number', format: 'float', nullable: true, example: null, description: 'The maximum amount range'),
        new OAT\Property(property: 'units', type: 'string', example: 'ml', description: 'The units of measurement'),
        new OAT\Property(property: 'note', type: 'string', nullable: true, example: 'freshly squeezed', description: 'Optional note for this part'),
        new OAT\Property(property: 'ingredient', type: IngredientBasicResource::class, description: 'The ingredient used in this part'),
    ],
    required: ['amount', 'units', 'note', 'ingredient']
)]
class IngredientPartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray($request)
    {
        return [
            'amount' => $this->amount,
            'amount_max' => $this->amount_max,
            'units' => $this->units,
            'note' => $this->note,
            'ingredient' => new IngredientBasicResource($this->ingredient),
        ];
    }
}
