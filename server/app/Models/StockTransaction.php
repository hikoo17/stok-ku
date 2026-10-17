<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'transaction_number',
    'product_id',
    'transaction_type',
    'quantity',
    'stock_before',
    'stock_after',
    'unit_price',
    'transaction_date',
    'description',
    'notes',
    'created_by',
    'idempotency_key',
])]
class StockTransaction extends Model
{
    public const TYPE_IN = 'in';

    public const TYPE_OUT = 'out';

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'stock_before' => 'decimal:3',
            'stock_after' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'transaction_date' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeIncoming(Builder $query): void
    {
        $query->where('transaction_type', self::TYPE_IN);
    }

    public function scopeOutgoing(Builder $query): void
    {
        $query->where('transaction_type', self::TYPE_OUT);
    }
}
