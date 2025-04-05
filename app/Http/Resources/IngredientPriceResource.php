<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\ValueObjects\Price;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\IngredientPrice
 */
#[OAT\Schema(
    schema: 'IngredientPrice',
    description: 'Ingredient price',
    properties: [
        new OAT\Property(type: PriceCategoryResource::class, property: 'price_category'),
        new OAT\Property(type: PriceResource::class, property: 'price'),
        new OAT\Property(type: 'number', example: 30.0, property: 'amount'),
        new OAT\Property(type: 'string', example: 'ml', property: 'units'),
        new OAT\Property(type: 'string', nullable: true, example: 'Updated price', property: 'description'),
        new OAT\Property(property: 'created_at', format: 'date-time', type: 'string'),
        new OAT\Property(property: 'updated_at', format: 'date-time', type: 'string', nullable: true),
    ],
    required: ['price_category', 'price', 'amount', 'units', 'description', 'created_at', 'updated_at']
)]
class IngredientPriceResource extends JsonResource
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
            'price_category' => new PriceCategoryResource($this->priceCategory),
            'price' => new PriceResource(new Price($this->getMoney())),
            'units' => $this->units,
            'amount' => $this->amount,
            'description' => $this->description,
            'created_at' => $this->created_at->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
        ];
    }
}
