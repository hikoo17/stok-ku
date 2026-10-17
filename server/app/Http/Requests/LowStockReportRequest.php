<?php

namespace App\Http\Requests;

class LowStockReportRequest extends ReportRequest
{
    public function rules(): array
    {
        return array_merge($this->commonRules(), [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}
