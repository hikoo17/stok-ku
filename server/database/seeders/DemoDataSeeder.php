<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\Unit;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;

/**
 * Illustrative demo data for local development only.
 * These are not verified business inventory records.
 */
class DemoDataSeeder extends Seeder
{
    public function run(InventoryService $inventory): void
    {
        $products = [
            [
                'sku' => 'RH-001',
                'name' => 'Radiator Hose 1.5 Inch',
                'category' => 'Radiator Hose',
                'unit' => 'pcs',
                'purchase_price' => 85000,
                'selling_price' => 120000,
                'opening_stock' => 24,
                'minimum_stock' => 5,
                'description' => 'Selang radiator fleksibel untuk kendaraan penumpang.',
            ],
            [
                'sku' => 'LSH-002',
                'name' => 'L-Shaped Hose 3/4 Inch',
                'category' => 'L-Shaped Hose',
                'unit' => 'pcs',
                'purchase_price' => 45000,
                'selling_price' => 65000,
                'opening_stock' => 3,
                'minimum_stock' => 5,
                'description' => 'Selang berbentuk L untuk saluran pendingin.',
            ],
            [
                'sku' => 'STH-003',
                'name' => 'Straight Hose 1 Meter',
                'category' => 'Straight Hose / Long Length',
                'unit' => 'm',
                'purchase_price' => 35000,
                'selling_price' => 50000,
                'opening_stock' => 42.5,
                'minimum_stock' => 10,
                'description' => 'Selang lurus dijual per meter.',
            ],
            [
                'sku' => 'TBO-004',
                'name' => 'Turbo Assembly T3',
                'category' => 'Turbo',
                'unit' => 'set',
                'purchase_price' => 3250000,
                'selling_price' => 4100000,
                'opening_stock' => 3,
                'minimum_stock' => 5,
                'description' => 'Unit turbo pengganti untuk mesin diesel.',
            ],
            [
                'sku' => 'BPS-005',
                'name' => 'Bypass Hose 5/8 Inch',
                'category' => 'Bypass',
                'unit' => 'pcs',
                'purchase_price' => 40000,
                'selling_price' => 58000,
                'opening_stock' => 12,
                'minimum_stock' => 4,
                'description' => 'Selang bypass pendingin ukuran 5/8 inci.',
            ],
            [
                'sku' => 'BLW-006',
                'name' => 'Blower Motor 12V',
                'category' => 'Blower',
                'unit' => 'pcs',
                'purchase_price' => 475000,
                'selling_price' => 620000,
                'opening_stock' => 7,
                'minimum_stock' => 2,
                'description' => 'Motor blower 12 volt untuk sistem ventilasi.',
            ],
            [
                'sku' => 'FTK-007',
                'name' => 'Fuel Tank 40 Liter',
                'category' => 'Fuel Tank',
                'unit' => 'pcs',
                'purchase_price' => 1250000,
                'selling_price' => 1650000,
                'opening_stock' => 2,
                'minimum_stock' => 1,
                'description' => 'Tangki bahan bakar kapasitas 40 liter.',
            ],
            [
                'sku' => 'VCM-008',
                'name' => 'Vacuum Pump 1/4 HP',
                'category' => 'Vacuum',
                'unit' => 'set',
                'purchase_price' => 1850000,
                'selling_price' => 2350000,
                'opening_stock' => 0,
                'minimum_stock' => 2,
                'description' => 'Pompa vakum untuk kebutuhan servis pendingin.',
            ],
            [
                'sku' => 'OLC-009',
                'name' => 'Oil Cooler Universal',
                'category' => 'Oil Cooler',
                'unit' => 'pcs',
                'purchase_price' => 725000,
                'selling_price' => 950000,
                'opening_stock' => 9,
                'minimum_stock' => 3,
                'description' => 'Pendingin oli universal untuk mesin.',
            ],
            [
                'sku' => 'RLL-010',
                'name' => 'Hose Roll 50 Meter',
                'category' => 'Straight Hose / Long Length',
                'unit' => 'roll',
                'purchase_price' => 950000,
                'selling_price' => 1250000,
                'opening_stock' => 4,
                'minimum_stock' => 2,
                'description' => 'Gulungan selang panjang 50 meter.',
            ],
        ];

        foreach ($products as $item) {
            $category = Category::query()->where('name', $item['category'])->firstOrFail();
            $unit = Unit::query()->where('abbreviation', $item['unit'])->firstOrFail();

            $product = Product::query()->firstOrCreate(
                ['sku' => $item['sku']],
                [
                    'name' => $item['name'],
                    'category_id' => $category->id,
                    'unit_id' => $unit->id,
                    'purchase_price' => $item['purchase_price'],
                    'selling_price' => $item['selling_price'],
                    'current_stock' => 0,
                    'minimum_stock' => $item['minimum_stock'],
                    'description' => $item['description'],
                    'is_active' => true,
                ],
            );

            if ($product->wasRecentlyCreated && $item['opening_stock'] > 0) {
                $inventory->recordOpeningStock($product, (float) $item['opening_stock']);
                $product->refresh();
            }
        }

        $this->seedMovements($inventory);
    }

    private function seedMovements(InventoryService $inventory): void
    {
        $radiatorHose = Product::query()->where('sku', 'RH-001')->first();
        $straightHose = Product::query()->where('sku', 'STH-003')->first();
        $fuelTank = Product::query()->where('sku', 'FTK-007')->first();

        if (! $radiatorHose || ! $straightHose || ! $fuelTank) {
            return;
        }

        $hasDemoMovements = StockTransaction::query()
            ->where('idempotency_key', 'like', 'demo-%')
            ->exists();

        if ($hasDemoMovements) {
            return;
        }

        $inventory->recordStockIn([
            'product_id' => $radiatorHose->id,
            'quantity' => 10,
            'unit_price' => 87000,
            'transaction_date' => now()->subDays(3)->setTime(9, 30)->toDateTimeString(),
            'description' => 'PT Sinar Parts',
            'notes' => 'Contoh transaksi barang masuk.',
            'idempotency_key' => 'demo-stock-in-radiator',
        ]);

        $inventory->recordStockOut([
            'product_id' => $radiatorHose->id,
            'quantity' => 6,
            'unit_price' => 125000,
            'transaction_date' => now()->subDays(2)->setTime(14, 15)->toDateTimeString(),
            'description' => 'Bengkel Jaya Motor',
            'notes' => 'Contoh transaksi barang keluar.',
            'idempotency_key' => 'demo-stock-out-radiator',
        ]);

        $inventory->recordStockOut([
            'product_id' => $straightHose->id,
            'quantity' => 12.5,
            'unit_price' => 50000,
            'transaction_date' => now()->subDay()->setTime(11, 0)->toDateTimeString(),
            'description' => 'Bengkel Sumber Rejeki',
            'notes' => 'Pengambilan selang per meter.',
            'idempotency_key' => 'demo-stock-out-straight-hose',
        ]);

        $inventory->recordStockIn([
            'product_id' => $fuelTank->id,
            'quantity' => 1,
            'unit_price' => 1260000,
            'transaction_date' => now()->setTime(8, 45)->toDateTimeString(),
            'description' => 'CV Mitra Otomotif',
            'notes' => 'Contoh transaksi barang masuk hari ini.',
            'idempotency_key' => 'demo-stock-in-fuel-tank',
        ]);
    }
}
