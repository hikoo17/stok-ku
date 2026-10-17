<?php

namespace Tests\Concerns;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Support\Str;

trait CreatesInventoryData
{
    protected function createCategory(array $attributes = []): Category
    {
        $name = $attributes['name'] ?? 'Kategori '.Str::random(6);

        return Category::query()->create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => $attributes['description'] ?? null,
            'is_active' => $attributes['is_active'] ?? true,
        ]);
    }

    protected function createUnit(array $attributes = []): Unit
    {
        return Unit::query()->create([
            'name' => $attributes['name'] ?? 'Satuan '.Str::random(6),
            'abbreviation' => $attributes['abbreviation'] ?? Str::lower(Str::random(5)),
            'is_active' => $attributes['is_active'] ?? true,
        ]);
    }

    protected function createProduct(array $attributes = []): Product
    {
        return Product::query()->create([
            'sku' => $attributes['sku'] ?? 'SKU-'.Str::upper(Str::random(6)),
            'name' => $attributes['name'] ?? 'Barang '.Str::random(6),
            'category_id' => $attributes['category_id'] ?? $this->createCategory()->id,
            'unit_id' => $attributes['unit_id'] ?? $this->createUnit()->id,
            'purchase_price' => $attributes['purchase_price'] ?? 10000,
            'selling_price' => $attributes['selling_price'] ?? 15000,
            'current_stock' => $attributes['current_stock'] ?? 10,
            'minimum_stock' => $attributes['minimum_stock'] ?? 2,
            'description' => $attributes['description'] ?? null,
            'is_active' => $attributes['is_active'] ?? true,
        ]);
    }
}
