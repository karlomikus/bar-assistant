<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Kami\Cocktail\External\Import\DuplicateActionsEnum;

class ImportFileRequest extends FormRequest
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
            'file' => 'required|file|mimes:zip|max:1048576', // 1 GB
            'bar_id' => 'required|integer',
            'duplicate_actions' => [Rule::enum(DuplicateActionsEnum::class)]
        ];
    }
}
