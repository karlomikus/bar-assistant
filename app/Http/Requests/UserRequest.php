<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Requests;

use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use Kami\Cocktail\Models\Enums\UserRoleEnum;

class UserRequest extends FormRequest
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
            'role_id' => ['required', new Enum(UserRoleEnum::class)],
            'email' => ['email'],
        ];
    }
}
