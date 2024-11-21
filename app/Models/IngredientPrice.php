<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IngredientPrice extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\IngredientPriceFactory> */
    use HasFactory;

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

    public function getAmount(): AmountValueObject
    {
        return new AmountValueObject($this->amount, new UnitValueObject($this->units));
    }

    public function getPricePerUnit(?string $units = null): Money
    {
        $amount = $this->getAmount()->amountMin;
        if ($units) {
            $amount = $this->getAmount()->convertTo(new UnitValueObject($units))->amountMin;
        }

        return $this->getMoney()->dividedBy($amount, RoundingMode::HALF_EVEN);
    }
}
