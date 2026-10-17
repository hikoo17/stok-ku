<?php

namespace App\Http\Requests;

class UpdateProductRequest extends ProductRequest
{
    public function rules(): array
    {
        return array_merge($this->sharedRules(), [
            // Stock may only change through audited stock transactions.
            'opening_stock' => ['prohibited'],
        ]);
    }
}
