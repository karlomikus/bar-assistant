<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImageRequest extends FormRequest
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
            'images' => 'array',
            'images.*.id' => 'sometimes|integer',
            // 'images.*.image' => 'sometimes|image|max:51200',
            // 'images.*.image_url' => 'sometimes|url',
            'images.*.sort' => 'integer',
        ];
    }
}
