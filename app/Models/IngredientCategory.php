<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Kalnoy\Nestedset\NodeTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kami\Cocktail\Models\Concerns\HasBarAwareScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IngredientCategory extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\IngredientCategoryFactory> */
    use HasFactory;
    use HasBarAwareScope;
    use NodeTrait;

    /**
     * @return HasMany<Ingredient>
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }

    /**
     * @return BelongsTo<Bar, IngredientCategory>
     */
    public function bar(): BelongsTo
    {
        return $this->belongsTo(Bar::class);
    }

    /**
     * @return array<string>
     */
    protected function getScopeAttributes(): array
    {
        return ['bar_id'];
    }
}
