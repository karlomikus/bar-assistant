<?php

namespace Kami\Cocktail\Rules;

use Closure;
use Kami\Cocktail\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;

class SubscriberImagesCount implements ValidationRule
{
    public function __construct(private readonly int $maxSubscriberImages, private readonly User $authenticatedUser)
    {
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!config('bar-assistant.enable_billing')) {
            return;
        }

        if ($value && !is_array($value)) {
            $value = [$value];
        }

        $value = (array) $value;

        if (!$this->authenticatedUser->hasActiveSubscription() && (count($value) > $this->maxSubscriberImages)) {
            $fail('Total images must be less than ' . $this->maxSubscriberImages);
        }
    }
}
