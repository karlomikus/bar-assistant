<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Kami\Cocktail\Rules\SubscriberImagesCount;
use Kami\Cocktail\Rules\NoReservedFilterDelimiter;

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
            'tags' => 'array',
            'ingredients' => 'array',
            'images' => [
                'array',
                new SubscriberImagesCount(1, $this->user()),
                'max:10',
            ],
            'images.*' => 'integer',
            'glass_id' => 'nullable|integer',
            'cocktail_method_id' => 'nullable|integer',
            'parent_cocktail_id' => 'nullable|integer',
            'utensils' => 'array',
            'utensils.*' => 'integer',
            'ingredients.*.ingredient_id' => 'required|integer',
            'ingredients.*.units' => 'required|string',
            'ingredients.*.amount' => 'required|numeric',
            'ingredients.*.amount_max' => 'nullable|numeric',
            'ingredients.*.optional' => 'boolean',
            'ingredients.*.substitutes' => 'array',
            'ingredients.*.substitutes.*.ingredient_id' => 'required|integer',
            'ingredients.*.substitutes.*.amount' => 'nullable|numeric',
            'ingredients.*.substitutes.*.amount_max' => 'nullable|numeric',
            'ingredients.*.substitutes.*.units' => 'nullable|string',
            'author' => ['nullable', 'string', new NoReservedFilterDelimiter()],
            'origin_bar' => ['nullable', 'string', new NoReservedFilterDelimiter()],
        ];
    }
}
