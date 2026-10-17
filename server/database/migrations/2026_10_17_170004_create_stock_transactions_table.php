<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            // "in" covers both opening stock and regular incoming stock.
            $table->string('transaction_type', 10);
            $table->decimal('quantity', 15, 3);
            $table->decimal('stock_before', 15, 3);
            $table->decimal('stock_after', 15, 3);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->dateTime('transaction_date');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            // Idempotency key supplied by the client to make retries safe.
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->timestamps();

            $table->index(['transaction_date', 'transaction_type']);
            $table->index(['product_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
    }
};
