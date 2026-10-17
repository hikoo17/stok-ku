<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rules shared by product creation and updates.
     *
     * Existing products may keep an inactive category or unit, but new
     * selections must reference active records.
     */
    protected function sharedRules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');

        return [
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'sku')->ignore($product?->id),
            ],
            'name' => ['required', 'string', 'max:150'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($product) {
                    $query->where('is_active', true);
                    if ($product) {
                        $query->orWhere('id', $product->category_id);
                    }
                }),
            ],
            'unit_id' => [
                'required',
                'integer',
                Rule::exists('units', 'id')->where(function ($query) use ($product) {
                    $query->where('is_active', true);
                    if ($product) {
                        $query->orWhere('id', $product->unit_id);
                    }
                }),
            ],
            'purchase_price' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'minimum_stock' => ['required', 'numeric', 'min:0', 'max:999999999999.999'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
