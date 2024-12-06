<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Kami\Cocktail\Models\Enums\BarStatusEnum;

class BarRequest extends FormRequest
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
            'enable_invites' => 'boolean',
            'options' => 'array',
            'default_units' => 'string',
            'default_lang' => 'string',
            'status' => [
                Rule::enum(BarStatusEnum::class),
                'nullable',
            ],
            'images' => 'array|max:1',
        ];
    }
}
