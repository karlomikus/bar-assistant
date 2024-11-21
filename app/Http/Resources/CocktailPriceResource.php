<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Kami\Cocktail\Models\Price;
use Kami\Cocktail\Models\CocktailIngredient;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\CocktailPrice
 */
class CocktailPriceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $prices = $this->cocktail->ingredients->map(function (CocktailIngredient $cocktailIngredient) {
            $minIngredientPrice = $cocktailIngredient->getMinConvertedPriceInCategory($this->priceCategory);
            if ($minIngredientPrice === null) {
                return null;
            }

            return [
                'units' => $minIngredientPrice->getAmount()->units,
                'ingredient' => new IngredientBasicResource($cocktailIngredient->ingredient),
                'price_per_unit' => new PriceResource(new Price($minIngredientPrice->getPricePerUnit($cocktailIngredient->units))),
                'price_per_use' => new PriceResource(new Price($cocktailIngredient->getConvertedPricePerUse($this->priceCategory))),
            ];
        })->filter()->values();

        return [
            'missing_prices_count' => $this->cocktail->ingredients->count() - $prices->count(),
            'price_category' => new PriceCategoryResource($this->priceCategory),
            'total_price' => new PriceResource(new Price($this->cocktail->calculatePrice($this->priceCategory))),
            'prices_per_ingredient' => $prices,
        ];
    }
}