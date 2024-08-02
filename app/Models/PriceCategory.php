<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use PrinsFrank\Standards\Currency\CurrencyAlpha3;
use Kami\Cocktail\Models\Concerns\HasBarAwareScope;

class PriceCategory extends Model
{
    use HasBarAwareScope;

    public $timestamps = false;

    public function getCurrency(): CurrencyAlpha3
    {
        return CurrencyAlpha3::from($this->currency);
    }
}
