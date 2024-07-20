<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Brick\Math\RoundingMode;
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
            'price' => $this->getMoney()->getAmount(),
            'price_formatted' => (string) $this->getMoney(),
            'price_per_unit' => (string) $this->getMoney()->getAmount()->dividedBy($this->amount, roundingMode: RoundingMode::UP),
            'amount' => $this->amount,
            'units' => $this->units,
            'description' => $this->description,
        ];
    }
}
