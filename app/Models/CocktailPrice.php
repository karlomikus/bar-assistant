<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

readonly class CocktailPrice
{
    public function __construct(
        public PriceCategory $priceCategory,
        public Cocktail $cocktail,
    ) {
    }
}
