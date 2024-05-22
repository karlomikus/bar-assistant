<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Kami\Cocktail\Models\Concerns\HasAuthors;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kami\Cocktail\Models\Concerns\HasBarAwareScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Glass extends Model
{
    use HasFactory;
    use HasBarAwareScope;
    use HasAuthors;

    /**
     * @return Attribute<?float, ?float>
     */
    protected function volume(): Attribute
    {
        return Attribute::make(
            get: function (?float $value) {
                if ($value <= 0.0) {
                    return null;
                }

                return $value;
            },
            set: function (?float $value) {
                if ($value <= 0.0) {
                    return null;
                }

                return $value;
            },
        );
    }

    /**
     * @return HasMany<Cocktail>
     */
    public function cocktails(): HasMany
    {
        return $this->hasMany(Cocktail::class);
    }

    /**
     * @return BelongsTo<Bar, Glass>
     */
    public function bar(): BelongsTo
    {
        return $this->belongsTo(Bar::class);
    }

    public function delete(): bool
    {
        $this->cocktails->each(fn ($cocktail) => $cocktail->searchable());

        return parent::delete();
    }
}
