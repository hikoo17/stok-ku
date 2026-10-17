<?php

namespace App\Http\Requests;

class StockMovementReportRequest extends ReportRequest
{
    public function rules(): array
    {
        return array_merge($this->commonRules(), [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'transaction_type' => ['nullable', 'in:in,out'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}
