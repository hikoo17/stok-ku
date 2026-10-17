<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_number' => $this->transaction_number,
            'transaction_type' => $this->transaction_type,
            'transaction_type_label' => $this->transaction_type === 'in' ? 'Barang Masuk' : 'Barang Keluar',
            'quantity' => (float) $this->quantity,
            'stock_before' => (float) $this->stock_before,
            'stock_after' => (float) $this->stock_after,
            'unit_price' => (float) $this->unit_price,
            'transaction_date' => $this->transaction_date?->toISOString(),
            'description' => $this->description,
            'notes' => $this->notes,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'sku' => $this->product->sku,
                'name' => $this->product->name,
                'is_active' => (bool) $this->product->is_active,
                'unit' => $this->product->relationLoaded('unit') && $this->product->unit ? [
                    'id' => $this->product->unit->id,
                    'name' => $this->product->unit->name,
                    'abbreviation' => $this->product->unit->abbreviation,
                ] : null,
            ]),
            'created_by' => $this->whenLoaded('createdBy', fn () => $this->createdBy?->only(['id', 'name'])),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
