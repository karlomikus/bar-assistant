<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Brick\Money\Currency;
use Illuminate\Database\Eloquent\Model;
use Kami\Cocktail\Models\Concerns\HasBarAwareScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PriceCategory extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\PriceCategoryFactory> */
    use HasFactory;
    use HasBarAwareScope;

    public $timestamps = false;

    public function getCurrency(): Currency
    {
        return Currency::of($this->currency);
    }

    /**
     * @return BelongsTo<Bar, $this>
     */
    public function bar(): BelongsTo
    {
        return $this->belongsTo(Bar::class);
    }
}
