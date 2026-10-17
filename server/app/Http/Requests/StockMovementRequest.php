<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class StockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.999'],
            'unit_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'idempotency_key' => ['nullable', 'uuid', 'max:100'],
        ];
    }
}
