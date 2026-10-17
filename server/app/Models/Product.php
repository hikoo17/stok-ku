<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'sku',
    'name',
    'category_id',
    'unit_id',
    'purchase_price',
    'selling_price',
    'current_stock',
    'minimum_stock',
    'description',
    'is_active',
])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'current_stock' => 'decimal:3',
            'minimum_stock' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeLowStock(Builder $query): void
    {
        $query->where('current_stock', '>', 0)
            ->whereColumn('current_stock', '<=', 'minimum_stock');
    }

    public function scopeOutOfStock(Builder $query): void
    {
        $query->where('current_stock', '<=', 0);
    }

    /**
     * Operational stock status derived from the product balance and its threshold.
     */
    public function getStockStatusAttribute(): string
    {
        if ((float) $this->current_stock <= 0) {
            return 'out_of_stock';
        }

        if ((float) $this->current_stock <= (float) $this->minimum_stock) {
            return 'low_stock';
        }

        return 'in_stock';
    }
}
