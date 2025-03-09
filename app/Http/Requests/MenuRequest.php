<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Requests;

use Illuminate\Validation\Rule;
use Kami\Cocktail\Rules\ValidCurrency;
use Illuminate\Foundation\Http\FormRequest;
use Kami\Cocktail\Models\Enums\MenuItemTypeEnum;

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
            'items' => 'required|array',
            'items.*.id' => 'required|integer',
            'items.*.type' => ['required', Rule::enum(MenuItemTypeEnum::class)],
            'items.*.sort' => 'required|integer',
            'items.*.currency' => ['required_with:items.*.price', 'size:3', new ValidCurrency()],
        ];
    }
}
