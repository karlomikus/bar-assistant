<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Kami\Cocktail\Models\ValueObjects\Price;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\IngredientPrice
 */
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
