<?php

namespace App\Http\Requests;

class StoreProductRequest extends ProductRequest
{
    public function rules(): array
    {
        return array_merge($this->sharedRules(), [
            'opening_stock' => ['nullable', 'numeric', 'min:0', 'max:999999999999.999'],
        ]);
    }
}
