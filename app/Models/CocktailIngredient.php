<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Kami\RecipeUtils\Converter;
use Illuminate\Database\Eloquent\Model;
use Kami\RecipeUtils\UnitConverter\Units;
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
        return $this->convertAttributeToUnits('amount', $toUnits);
    }

    public function getMaxAmountAsUnit(Units $toUnits): ?float
    {
        return $this->convertAttributeToUnits('amount_max', $toUnits);
    }

    public function printIngredient(): string
    {
        return sprintf('%s %s', $this->printAmount(), $this->ingredient->name);
    }

    public function printAmount(): string
    {
        $str = sprintf('%s %s', $this->amount, $this->units);
        if ($this->amount_max) {
            $str .= sprintf(' - %s %s', $this->amount_max, $this->units);
        }

        return $str;
    }

    private function convertAttributeToUnits(string $attribute, Units $toUnits): ?float
    {
        if (!$this->{$attribute}) {
            return null;
        }

        if ($fromUnits = Units::tryFrom($this->units)) {
            if ($fromUnits === Units::Dash) {
                return null;
            }

            return Converter::fromTo($this->{$attribute}, $fromUnits, $toUnits);
        }

        return null;
    }
}
