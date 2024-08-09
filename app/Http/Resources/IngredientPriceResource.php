<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

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
            'price_minor' => $this->getMoney()->getMinorAmount(),
            'price_formatted' => (string) $this->getMoney(),
            'amount' => $this->amount,
            'units' => $this->units,
            'description' => $this->description,
            'created_at' => $this->created_at->toJson(),
            'updated_at' => $this->updated_at?->toJson(),
        ];
    }
}
