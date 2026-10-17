<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesInventoryData;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use CreatesInventoryData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_product_can_be_created_with_opening_stock(): void
    {
        $category = $this->createCategory();
        $unit = $this->createUnit();

        $response = $this->postJson('/api/v1/products', [
            'sku' => 'PRD-001',
            'name' => 'Selang Uji',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'purchase_price' => 20000,
            'selling_price' => 30000,
            'opening_stock' => 12.5,
            'minimum_stock' => 3,
            'description' => 'Produk untuk pengujian.',
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.sku', 'PRD-001')
            ->assertJsonPath('data.current_stock', 12.5)
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.unit.id', $unit->id);

        $product = Product::query()->where('sku', 'PRD-001')->firstOrFail();
        $this->assertSame('12.500', $product->current_stock);

        $this->assertDatabaseHas('stock_transactions', [
            'product_id' => $product->id,
            'transaction_type' => StockTransaction::TYPE_IN,
            'quantity' => 12.5,
            'stock_before' => 0,
            'stock_after' => 12.5,
        ]);

        $this->assertDatabaseHas('stock_transactions', [
            'product_id' => $product->id,
            'idempotency_key' => 'opening-product-'.$product->id,
        ]);
    }

    public function test_duplicate_sku_is_rejected(): void
    {
        $this->createProduct(['sku' => 'SAME-001']);
        $category = $this->createCategory();
        $unit = $this->createUnit();

        $response = $this->postJson('/api/v1/products', [
            'sku' => 'SAME-001',
            'name' => 'Barang Duplikat',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'purchase_price' => 1000,
            'selling_price' => 2000,
            'opening_stock' => 0,
            'minimum_stock' => 1,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('sku');
    }

    public function test_inactive_category_cannot_be_selected_for_new_product(): void
    {
        $category = $this->createCategory(['is_active' => false]);
        $unit = $this->createUnit();

        $response = $this->postJson('/api/v1/products', [
            'sku' => 'NEW-001',
            'name' => 'Barang Baru',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'purchase_price' => 1000,
            'selling_price' => 2000,
            'minimum_stock' => 1,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('category_id');
    }

    public function test_updating_a_product_does_not_change_stock_directly(): void
    {
        $product = $this->createProduct(['current_stock' => 10]);

        $response = $this->putJson("/api/v1/products/{$product->id}", [
            'sku' => $product->sku,
            'name' => 'Nama Baru',
            'category_id' => $product->category_id,
            'unit_id' => $product->unit_id,
            'purchase_price' => 11000,
            'selling_price' => 16000,
            'minimum_stock' => 4,
        ]);

        $response->assertOk()->assertJsonPath('data.current_stock', 10);

        $this->assertSame('10.000', $product->refresh()->current_stock);

        $this->putJson("/api/v1/products/{$product->id}", [
            'sku' => $product->sku,
            'name' => 'Nama Baru',
            'category_id' => $product->category_id,
            'unit_id' => $product->unit_id,
            'purchase_price' => 11000,
            'selling_price' => 16000,
            'minimum_stock' => 4,
            'current_stock' => 999,
        ])->assertOk();

        $this->assertSame('10.000', $product->refresh()->current_stock);
    }

    public function test_product_list_supports_stock_status_filter_and_pagination(): void
    {
        $this->createProduct(['sku' => 'SAFE-001', 'current_stock' => 10, 'minimum_stock' => 2]);
        $this->createProduct(['sku' => 'LOW-001', 'current_stock' => 2, 'minimum_stock' => 2]);
        $this->createProduct(['sku' => 'OUT-001', 'current_stock' => 0, 'minimum_stock' => 2]);

        $this->getJson('/api/v1/products?stock_status=low_stock')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sku', 'LOW-001');

        $this->getJson('/api/v1/products?stock_status=out_of_stock')
            ->assertOk()
            ->assertJsonPath('data.0.sku', 'OUT-001');

        $this->getJson('/api/v1/products?search=SAFE')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_archiving_a_product_keeps_its_history(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $product = $this->createProduct();

        $this->postJson('/api/v1/stock-in', [
            'product_id' => $product->id,
            'quantity' => 5,
            'transaction_date' => now()->toDateString(),
        ])->assertCreated();

        $this->deleteJson("/api/v1/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);
        $this->assertDatabaseCount('stock_transactions', 1);

        $this->getJson('/api/v1/stock-transactions')
            ->assertOk()
            ->assertJsonPath('data.0.product.sku', $product->sku);
    }

    public function test_missing_product_returns_an_indonesian_message(): void
    {
        $this->getJson('/api/v1/products/999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Data atau halaman yang diminta tidak ditemukan.');
    }
}
