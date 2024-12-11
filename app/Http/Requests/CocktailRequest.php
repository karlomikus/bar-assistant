<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Kami\Cocktail\Rules\SubscriberImagesCount;

class CocktailRequest extends FormRequest
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
            'name' => 'required',
            'instructions' => 'required',
            'ingredients' => 'array',
            'images' => [
                'array',
                new SubscriberImagesCount(1, $this->user()),
                'max:10',
            ],
            'images.*' => 'integer',
            'ingredients.*.ingredient_id' => 'required|integer',
            'ingredients.*.units' => 'required_with:ingredients.*.amount',
            'ingredients.*.amount' => 'required_with:ingredients.*.units|numeric',
            'ingredients.*.amount_max' => 'nullable|numeric',
            'ingredients.*.optional' => 'boolean',
            'ingredients.*.ingredient.substitutes' => 'array',
            'ingredients.*.ingredient.substitutes.*.ingredient_id' => 'integer',
            'ingredients.*.ingredient.substitutes.*.amount' => 'numeric',
            'ingredients.*.ingredient.substitutes.*.amount_max' => 'nullable|numeric',
        ];
    }
}
