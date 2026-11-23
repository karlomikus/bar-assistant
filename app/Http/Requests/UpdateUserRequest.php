<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->user()?->getAuthIdentifier())],
            'name' => ['required', 'string', 'max:255'],
            'settings' => ['sometimes', 'nullable', 'array'],
            'settings.language' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.theme' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
