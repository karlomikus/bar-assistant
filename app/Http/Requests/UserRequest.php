<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Requests;

use Kami\Cocktail\Models\User;
use Illuminate\Validation\Rule;
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

        return [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'is_admin' => 'required|boolean',
        ];
    }
}
