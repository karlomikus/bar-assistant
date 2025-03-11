<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Kami\Cocktail\Rules\SubscriberImagesCount;

class IngredientRequest extends FormRequest
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
            'strength' => 'numeric',
            'sugar_g_per_ml' => 'nullable|numeric',
            'acidity' => 'nullable|numeric',
            'calculator_id' => 'nullable|integer',
            'parent_ingredient_id' => 'nullable|integer',
            'prices' => 'array',
            'complex_ingredient_part_ids' => 'array',
            'complex_ingredient_part_ids.*' => 'integer',
            'prices.*.price' => 'required|gte:0|decimal:0,2',
            'prices.*.amount' => 'required|numeric|gte:0',
            'prices.*.units' => 'required',
            'prices.*.price_category_id' => 'integer|required',
            'images' => [
                'array',
                new SubscriberImagesCount(1, $this->user()),
                'max:1',
            ],
            'images.*' => 'integer',
        ];
    }
}
