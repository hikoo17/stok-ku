<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $unit = $this->route('unit');

        return [
            'name' => ['required', 'string', 'max:50'],
            'abbreviation' => [
                'required',
                'string',
                'max:20',
                Rule::unique('units', 'abbreviation')->ignore($unit?->id),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
