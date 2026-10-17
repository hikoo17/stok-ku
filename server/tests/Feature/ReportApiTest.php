<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesInventoryData;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    use CreatesInventoryData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_dashboard_summary_uses_actual_inventory_data(): void
    {
        $this->createProduct(['current_stock' => 10, 'minimum_stock' => 2]);
        $this->createProduct(['current_stock' => 2, 'minimum_stock' => 2]);
        $this->createProduct(['current_stock' => 0, 'minimum_stock' => 2]);
        $this->createProduct(['current_stock' => 50, 'minimum_stock' => 2, 'is_active' => false]);

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.summary.total_products', 3)
            ->assertJsonPath('data.summary.total_stock', 12)
            ->assertJsonPath('data.summary.low_stock', 1)
            ->assertJsonPath('data.summary.out_of_stock', 1)
            ->assertJsonCount(2, 'data.low_stock_products');
    }

    public function test_inventory_report_filters_by_stock_status(): void
    {
        $this->createProduct(['sku' => 'SAFE-100', 'current_stock' => 10, 'minimum_stock' => 2]);
        $this->createProduct(['sku' => 'LOW-100', 'current_stock' => 1, 'minimum_stock' => 2]);
        $this->createProduct(['sku' => 'OUT-100', 'current_stock' => 0, 'minimum_stock' => 2]);

        $this->getJson('/api/v1/reports/inventory?stock_status=low_stock')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sku', 'LOW-100');

        $this->getJson('/api/v1/reports/inventory?search=OUT-100')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_low_stock_report_includes_out_of_stock_products(): void
    {
        $this->createProduct(['sku' => 'SAFE-200', 'current_stock' => 10, 'minimum_stock' => 2]);
        $this->createProduct(['sku' => 'LOW-200', 'current_stock' => 2, 'minimum_stock' => 2]);
        $this->createProduct(['sku' => 'OUT-200', 'current_stock' => 0, 'minimum_stock' => 2]);

        $this->getJson('/api/v1/reports/low-stock')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('summary.total', 2)
            ->assertJsonPath('summary.low_stock', 1)
            ->assertJsonPath('summary.out_of_stock', 1);
    }

    public function test_stock_movement_report_calculates_totals_for_filtered_period(): void
    {
        $product = $this->createProduct(['current_stock' => 10]);
        $service = app(InventoryService::class);

        $service->recordStockIn([
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 10000,
            'transaction_date' => now()->toDateTimeString(),
        ]);

        $service->recordStockOut([
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 15000,
            'transaction_date' => now()->toDateTimeString(),
        ]);

        $this->getJson('/api/v1/reports/stock-movements?'.http_build_query([
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('summary.total_in', 5)
            ->assertJsonPath('summary.total_out', 3)
            ->assertJsonPath('summary.total_transactions', 2);
    }

    public function test_report_export_uses_same_filters_and_escapes_formulas(): void
    {
        $category = $this->createCategory(['name' => 'Kategori Ekspor']);
        $this->createProduct([
            'sku' => 'EXP-001',
            'name' => '=HYPERLINK("http://example.test")',
            'category_id' => $category->id,
            'current_stock' => 4,
            'minimum_stock' => 1,
        ]);

        $response = $this->get('/api/v1/reports/inventory/export?category_id='.$category->id);

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('Laporan Stok Saat Ini', $content);
        $this->assertStringContainsString("'=HYPERLINK", $content);
        $this->assertStringContainsString('EXP-001', $content);
    }

    public function test_stock_movement_export_includes_date_range_and_totals(): void
    {
        $product = $this->createProduct(['current_stock' => 10]);
        app(InventoryService::class)->recordStockIn([
            'product_id' => $product->id,
            'quantity' => 2,
            'transaction_date' => now()->toDateTimeString(),
        ]);

        $response = $this->get('/api/v1/reports/stock-movements/export?'.http_build_query([
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('Laporan Pergerakan Stok', $content);
        $this->assertStringContainsString('Total barang masuk: 2', $content);
        $this->assertStringContainsString('TRX-IN', $content);
    }
}
