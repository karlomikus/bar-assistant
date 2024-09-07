<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Requests;

use Kami\Cocktail\Rules\ValidCurrency;
use Illuminate\Foundation\Http\FormRequest;

class MenuRequest extends FormRequest
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
            'is_enabled' => 'required|boolean',
            'cocktails' => 'required|array',
            'cocktails.*.cocktail_id' => 'required',
            'cocktails.*.sort' => 'required|integer',
            'cocktails.*.currency' => ['required_with:cocktails.*.price', 'size:3', new ValidCurrency()],
        ];
    }
}
