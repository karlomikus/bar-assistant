<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'prices' => 'array',
            'prices.*.price' => 'required|gte:0|decimal:0,2',
            'prices.*.amount' => 'required|numeric|gte:0',
            'prices.*.units' => 'required',
            'prices.*.price_category_id' => 'int|required',
            'images' => 'array|max:1',
        ];
    }
}
