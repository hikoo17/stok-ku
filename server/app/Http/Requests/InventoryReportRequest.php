<?php

namespace App\Http\Requests;

class InventoryReportRequest extends ReportRequest
{
    public function rules(): array
    {
        return array_merge($this->commonRules(), [
            'stock_status' => ['nullable', 'in:in_stock,low_stock,out_of_stock'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}
