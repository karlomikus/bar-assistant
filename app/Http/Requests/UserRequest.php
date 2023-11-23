<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Requests;

use Kami\Cocktail\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Kami\Cocktail\Models\UserRoleEnum;
use Illuminate\Foundation\Http\FormRequest;

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
        $user = User::where('email', $this->post('email'))->first();

        $rules = [
            'name' => 'required',
            'role_id' => ['required', new Enum(UserRoleEnum::class)],
            'email' => [
                'required',
                'email',
            ],
        ];

        if ($this->isMethod('POST')) {
            $rules['password'] = 'required|min:5';
            $rules['email'][] = Rule::unique('users', 'email');
        }

        if ($this->isMethod('PUT')) {
            $rules['email'][] = Rule::unique('users', 'email')->ignore($user);
        }

        return $rules;
    }
}
