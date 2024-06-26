<?php

declare(strict_types=1);

namespace Kami\Cocktail\Rules;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\ValidationRule;

class IngredientBelongsToBar implements ValidationRule
{
    public function __construct(private readonly int $barId)
    {
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value && !is_array($value)) {
            $value = [$value];
        }

        $dbIngredients = DB::table('ingredients')->select('id')->whereIn('id', $value)->where('bar_id', $this->barId)->count();
        if ($dbIngredients !== count($value)) {
            $fail('Selected ingredients are not part of the current bar');
        }
    }
}
