<?php

namespace Kami\Cocktail\Rules;

use Closure;
use Throwable;
use PrinsFrank\Standards\Currency\CurrencyAlpha3;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCurrency implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            CurrencyAlpha3::from($value);
        } catch (Throwable) {
            $fail('Currency must be in ISO 4217 (Alpha3) format');
        }
    }
}
