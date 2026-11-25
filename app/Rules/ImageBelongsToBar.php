<?php

declare(strict_types=1);

namespace Kami\Cocktail\Rules;

use Closure;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Image;
use Illuminate\Contracts\Validation\ValidationRule;

final class ImageBelongsToBar implements ValidationRule
{
    public function __construct(private readonly ?int $barId, private readonly int $userId)
    {
    }

    /**
     * Run the validation rule.
     *
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
            $fail('One or more of the selected images do not belong to the specified bar');

            return;
        }

        $imageId = is_int($value) ? $value : (int) $value;
        $image = Image::with('imageable')->find($imageId);

        if ($image === null) {
            $fail('One or more of the selected images do not belong to the specified bar');

            return;
        }

        if ($image->imageable_id === null) {
            if ($image->created_user_id !== $this->userId) {
                $fail('One or more of the selected images do not belong to the specified bar');
            }

            return;
        }

        $imageBarId = $image->imageable instanceof Bar
            ? $image->imageable->id
            : $image->imageable?->bar_id;

        if ($imageBarId !== $this->barId) {
            $fail('One or more of the selected images do not belong to the specified bar');
        }
    }
}
