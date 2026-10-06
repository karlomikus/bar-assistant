<?php

declare(strict_types=1);

namespace Kami\Cocktail\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoReservedFilterDelimiter implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && str_contains($value, '|')) {
            $fail('The :attribute field must not contain the "|" character because it is reserved as a filter value delimiter.');
        }
    }
}
