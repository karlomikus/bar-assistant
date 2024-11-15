<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Brick\Money\Money;
use Brick\Math\RoundingMode;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;
use Kami\RecipeUtils\UnitConverter\Units;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredientPrice extends Model
{
    /**
     * @return BelongsTo<Ingredient, $this>
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * @return BelongsTo<PriceCategory, $this>
     */
    public function priceCategory(): BelongsTo
    {
        return $this->belongsTo(PriceCategory::class);
    }

    public function getMoney(): Money
    {
        return Money::ofMinor($this->price, $this->priceCategory->currency);
    }

    public function getPricePerUnit(): Money
    {
        return $this->getMoney()->dividedBy($this->amount, RoundingMode::HALF_EVEN);
    }

    public function getPricePerPour(float $amount, ?Units $units): Money
    {
        if (!$units || Units::tryFrom($this->units) !== $units) {
            throw new InvalidArgumentException('Price per unit units do not match');
        }

        return $this->getPricePerUnit()->multipliedBy($amount, RoundingMode::HALF_EVEN);
    }
}
