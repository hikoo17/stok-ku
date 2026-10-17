<?php

namespace Tests\Feature;

use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesInventoryData;
use Tests\TestCase;

class StockTransactionApiTest extends TestCase
{
    use CreatesInventoryData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_stock_in_increases_stock_and_records_history(): void
    {
        $product = $this->createProduct(['current_stock' => 10]);

        $response = $this->postJson('/api/v1/stock-in', [
            'product_id' => $product->id,
            'quantity' => 5.5,
            'unit_price' => 12500,
            'transaction_date' => now()->toDateString(),
            'description' => 'Pemasok Uji',
            'notes' => 'Catatan uji',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.quantity', 5.5)
            ->assertJsonPath('data.stock_before', 10)
            ->assertJsonPath('data.stock_after', 15.5)
            ->assertJsonPath('data.unit_price', 12500);

        $this->assertSame('15.500', $product->refresh()->current_stock);

        $this->assertDatabaseHas('stock_transactions', [
            'product_id' => $product->id,
            'transaction_type' => StockTransaction::TYPE_IN,
            'quantity' => 5.5,
            'stock_before' => 10,
            'stock_after' => 15.5,
        ]);
    }

    public function test_stock_out_decreases_stock_and_records_history(): void
    {
        $product = $this->createProduct([
            'current_stock' => 10,
            'selling_price' => 18000,
        ]);

        $response = $this->postJson('/api/v1/stock-out', [
            'product_id' => $product->id,
            'quantity' => 4,
            'transaction_date' => now()->toDateString(),
            'description' => 'Penerima Uji',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.stock_before', 10)
            ->assertJsonPath('data.stock_after', 6)
            ->assertJsonPath('data.unit_price', 18000);

        $this->assertSame('6.000', $product->refresh()->current_stock);
    }

    public function test_stock_out_cannot_exceed_available_stock(): void
    {
        $product = $this->createProduct(['current_stock' => 3]);

        $response = $this->postJson('/api/v1/stock-out', [
            'product_id' => $product->id,
            'quantity' => 4,
            'transaction_date' => now()->toDateString(),
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('quantity');

        $this->assertSame('3.000', $product->refresh()->current_stock);
        $this->assertDatabaseCount('stock_transactions', 0);
    }

    public function test_zero_and_negative_quantities_are_rejected(): void
    {
        $product = $this->createProduct(['current_stock' => 10]);

        foreach ([0, -5] as $quantity) {
            $this->postJson('/api/v1/stock-in', [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'transaction_date' => now()->toDateString(),
            ])->assertStatus(422)->assertJsonValidationErrors('quantity');
        }

        $this->assertSame('10.000', $product->refresh()->current_stock);
        $this->assertDatabaseCount('stock_transactions', 0);
    }

    public function test_archived_product_cannot_be_transacted(): void
    {
        $product = $this->createProduct(['is_active' => false]);

        $this->postJson('/api/v1/stock-in', [
            'product_id' => $product->id,
            'quantity' => 1,
            'transaction_date' => now()->toDateString(),
        ])->assertStatus(422)->assertJsonValidationErrors('product_id');

        $this->assertDatabaseCount('stock_transactions', 0);
    }

    public function test_retried_request_with_same_idempotency_key_is_processed_once(): void
    {
        $product = $this->createProduct(['current_stock' => 10]);
        $key = (string) Str::uuid();

        $payload = [
            'product_id' => $product->id,
            'quantity' => 2,
            'transaction_date' => now()->toDateString(),
            'idempotency_key' => $key,
        ];

        $this->postJson('/api/v1/stock-in', $payload)->assertCreated();
        $this->postJson('/api/v1/stock-in', $payload)->assertCreated();

        $this->assertSame('12.000', $product->refresh()->current_stock);
        $this->assertDatabaseCount('stock_transactions', 1);
    }

    public function test_stock_out_is_rejected_when_product_has_no_stock(): void
    {
        $product = $this->createProduct(['current_stock' => 0]);

        $this->postJson('/api/v1/stock-out', [
            'product_id' => $product->id,
            'quantity' => 1,
            'transaction_date' => now()->toDateString(),
        ])->assertStatus(422)->assertJsonValidationErrors('quantity');

        $this->assertSame('0.000', $product->refresh()->current_stock);
        $this->assertDatabaseCount('stock_transactions', 0);
    }
}
