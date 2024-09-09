<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use PrinsFrank\Standards\Currency\CurrencyAlpha3;
use Kami\Cocktail\Models\Concerns\HasBarAwareScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceCategory extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\PriceCategoryFactory> */
    use HasFactory;
    use HasBarAwareScope;

    public $timestamps = false;

    public function getCurrency(): CurrencyAlpha3
    {
        return CurrencyAlpha3::from($this->currency);
    }

    /**
     * @return BelongsTo<Bar, PriceCategory>
     */
    public function bar(): BelongsTo
    {
        return $this->belongsTo(Bar::class);
    }
}
