<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesInventoryData;
use Tests\TestCase;

class CategoryUnitApiTest extends TestCase
{
    use CreatesInventoryData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        $category = $this->createCategory();
        $this->createProduct(['category_id' => $category->id]);

        $this->deleteJson("/api/v1/categories/{$category->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Kategori tidak dapat dihapus karena masih digunakan oleh barang.');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_unused_category_can_be_deleted(): void
    {
        $category = $this->createCategory();

        $this->deleteJson("/api/v1/categories/{$category->id}")->assertOk();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_has_a_unique_slug_based_on_name(): void
    {
        $this->postJson('/api/v1/categories', ['name' => 'Selang Uji'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'selang-uji');

        $this->postJson('/api/v1/categories', ['name' => 'Selang Uji'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_inactive_category_is_preserved_when_updating_existing_product(): void
    {
        $category = $this->createCategory();
        $unit = $this->createUnit();
        $product = $this->createProduct([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
        ]);

        $this->putJson("/api/v1/categories/{$category->id}", [
            'name' => $category->name,
            'is_active' => false,
        ])->assertOk();

        $this->putJson("/api/v1/products/{$product->id}", [
            'sku' => $product->sku,
            'name' => 'Nama Diperbarui',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'minimum_stock' => 1,
        ])->assertOk()->assertJsonPath('data.category.is_active', false);
    }

    public function test_unit_in_use_cannot_be_deleted(): void
    {
        $unit = $this->createUnit();
        $this->createProduct(['unit_id' => $unit->id]);

        $this->deleteJson("/api/v1/units/{$unit->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Satuan tidak dapat dihapus karena masih digunakan oleh barang.');

        $this->assertDatabaseHas('units', ['id' => $unit->id]);
    }

    public function test_abbreviation_must_be_unique(): void
    {
        $this->postJson('/api/v1/units', ['name' => 'Buah', 'abbreviation' => 'pcs'])
            ->assertCreated();

        $this->postJson('/api/v1/units', ['name' => 'Buah Lain', 'abbreviation' => 'pcs'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('abbreviation');
    }
}
