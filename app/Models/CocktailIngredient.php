<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Kami\RecipeUtils\Converter;
use Illuminate\Database\Eloquent\Model;
use Kami\RecipeUtils\UnitConverter\Units;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CocktailIngredient extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $casts = [
        'optional' => 'boolean',
    ];

    private ?Units $asUnits = null;

    /**
     * Should probably be careful with this since it changes the units on the
     * whole model reference, so it gets a bit "sticky"
     *
     * @param null|Units $units
     * @return CocktailIngredient
     */
    public function withUnits(?Units $units = null): self
    {
        $this->asUnits = $units;

        return $this;
    }

    public function ignoreUnits(): self
    {
        $this->asUnits = null;

        return $this;
    }

    /**
     * @return BelongsTo<Ingredient, CocktailIngredient>
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * @return BelongsTo<Cocktail, CocktailIngredient>
     */
    public function cocktail(): BelongsTo
    {
        return $this->belongsTo(Cocktail::class);
    }

    /**
     * @return HasMany<CocktailIngredientSubstitute>
     */
    public function substitutes(): HasMany
    {
        return $this->hasMany(CocktailIngredientSubstitute::class);
    }

    public function getAmountAsUnit(Units $toUnits): ?float
    {
        return $this->convertToUnits($this->amount, $toUnits);
    }

    public function getMaxAmountAsUnit(Units $toUnits): ?float
    {
        return $this->convertToUnits($this->amount_max, $toUnits);
    }

    public function printIngredient(): string
    {
        return sprintf('%s %s', $this->printAmount(), $this->ingredient->name);
    }

    public function printAmount(): string
    {
        $amountMin = $this->amount;
        $amountMax = $this->amount_max;
        $units = $this->units;

        $str = sprintf('%s %s', $amountMin, $units);
        if ($amountMax) {
            $str .= sprintf(' - %s %s', $amountMax, $units);
        }

        return $str;
    }

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: function (float $originalAmount) {
                if ($this->asUnits) {
                    return $this->convertToUnits($originalAmount, $this->asUnits) ?? $originalAmount;
                }

                return $originalAmount;
            },
        );
    }

    protected function amountMax(): Attribute
    {
        return Attribute::make(
            get: function (?float $originalAmount) {
                if (!$originalAmount) {
                    return null;
                }

                if ($this->asUnits) {
                    return $this->convertToUnits($originalAmount, $this->asUnits) ?? $originalAmount;
                }

                return $originalAmount;
            },
        );
    }

    protected function units(): Attribute
    {
        return Attribute::make(
            get: function (string $originalUnits) {
                $fromUnits = Units::tryFrom($originalUnits);
                // Dont convert dashes atm
                if ($fromUnits === Units::Dash) {
                    return $originalUnits;
                }

                if ($this->asUnits && Units::tryFrom($originalUnits)) {
                    return $this->asUnits->value;
                }

                return $originalUnits;
            },
        );
    }

    public function getUnitsAsEnum(): ?Units
    {
        return Units::tryFrom((string) $this->getOriginal('units'));
    }

    private function convertToUnits(?float $value, Units $toUnits): ?float
    {
        if ($value === null) {
            return null;
        }

        if ($this->getUnitsAsEnum()) {
            if ($this->getUnitsAsEnum() === Units::Dash) {
                return null;
            }

            return Converter::fromTo($value, $this->getUnitsAsEnum(), $toUnits);
        }

        return null;
    }
}
