<?php

declare(strict_types=1);

namespace Kami\Cocktail\Rules;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\ValidationRule;

class ResourceBelongsToBar implements ValidationRule
{
    public function __construct(private readonly int $barId, private readonly string $table, private readonly string $column = 'id')
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

        $count = DB::table($this->table)
            ->select($this->column)
            ->whereIn($this->column, $value)
            ->where('bar_id', $this->barId)
            ->count();

        if ($count !== count($value)) {
            $fail('Selected resources are not part of the current bar');
        }
    }
}
