<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class RatingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'rating' => [
                'required',
                'numeric',
                'min:1',
                'max:5',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (!is_numeric($value)) {
                        return;
                    }

                    if (fmod((float) $value * 2, 1.0) !== 0.0) {
                        $fail('The rating field must be on a 0.5 step.');
                    }
                },
            ],
        ];
    }
}
