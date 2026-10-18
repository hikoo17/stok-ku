<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function commonRules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'format' => ['nullable', 'in:csv,pdf,docx'],
        ];
    }
}
