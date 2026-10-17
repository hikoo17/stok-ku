<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Single authoritative place for changing inventory balances.
 *
 * Every mutation runs inside a database transaction and locks the product row
 * so concurrent stock-out requests cannot oversell the same balance.
 */
class InventoryService
{
    public function recordOpeningStock(Product $product, float $quantity, ?int $userId = null): ?StockTransaction
    {
        if ($quantity <= 0) {
            return null;
        }

        return DB::transaction(function () use ($product, $quantity, $userId) {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            $stockBefore = round((float) $locked->current_stock, 3);

            $transaction = $this->createTransaction(
                product: $locked,
                type: StockTransaction::TYPE_IN,
                quantity: $quantity,
                unitPrice: (float) $locked->purchase_price,
                transactionDate: now(),
                description: 'Stok awal produk',
                notes: null,
                userId: $userId,
                idempotencyKey: 'opening-product-'.$locked->id,
                stockBefore: $stockBefore,
                stockAfter: round($stockBefore + $quantity, 3),
            );

            $locked->forceFill([
                'current_stock' => $transaction->stock_after,
            ])->save();

            return $transaction;
        });
    }

    /**
     * @param  array{product_id:int, quantity:float|int|string, unit_price?:float|int|string|null, transaction_date:string, description?:string|null, notes?:string|null, idempotency_key?:string|null}  $data
     */
    public function recordStockIn(array $data, ?int $userId = null): StockTransaction
    {
        return $this->recordMovement(StockTransaction::TYPE_IN, $data, $userId);
    }

    /**
     * @param  array{product_id:int, quantity:float|int|string, unit_price?:float|int|string|null, transaction_date:string, description?:string|null, notes?:string|null, idempotency_key?:string|null}  $data
     */
    public function recordStockOut(array $data, ?int $userId = null): StockTransaction
    {
        return $this->recordMovement(StockTransaction::TYPE_OUT, $data, $userId);
    }

    private function recordMovement(string $type, array $data, ?int $userId): StockTransaction
    {
        $idempotencyKey = $data['idempotency_key'] ?? null;

        if ($idempotencyKey) {
            $existing = StockTransaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        try {
            return DB::transaction(function () use ($type, $data, $userId, $idempotencyKey) {
                $product = Product::query()->lockForUpdate()->findOrFail($data['product_id']);

                if (! $product->is_active) {
                    throw ValidationException::withMessages([
                        'product_id' => 'Barang ini sedang diarsipkan dan tidak dapat ditransaksikan.',
                    ]);
                }

                $quantity = round((float) $data['quantity'], 3);

                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Jumlah harus lebih besar dari nol.',
                    ]);
                }

                $stockBefore = round((float) $product->current_stock, 3);
                $stockAfter = $type === StockTransaction::TYPE_IN
                    ? round($stockBefore + $quantity, 3)
                    : round($stockBefore - $quantity, 3);

                if ($stockAfter < 0) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Jumlah keluar melebihi stok yang tersedia ('.$this->formatQuantity($stockBefore).').',
                    ]);
                }

                $unitPrice = array_key_exists('unit_price', $data) && $data['unit_price'] !== null
                    ? round((float) $data['unit_price'], 2)
                    : (float) ($type === StockTransaction::TYPE_IN ? $product->purchase_price : $product->selling_price);

                $transaction = $this->createTransaction(
                    product: $product,
                    type: $type,
                    quantity: $quantity,
                    unitPrice: $unitPrice,
                    transactionDate: $data['transaction_date'],
                    description: $data['description'] ?? null,
                    notes: $data['notes'] ?? null,
                    userId: $userId,
                    idempotencyKey: $idempotencyKey,
                    stockBefore: $stockBefore,
                    stockAfter: $stockAfter,
                );

                $product->forceFill(['current_stock' => $stockAfter])->save();

                return $transaction;
            });
        } catch (QueryException $exception) {
            // Two concurrent retries can pass the initial check. The unique
            // idempotency key resolves the race: return the stored transaction.
            if ($idempotencyKey) {
                $existing = StockTransaction::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            throw $exception;
        }
    }

    private function createTransaction(
        Product $product,
        string $type,
        float $quantity,
        float $unitPrice,
        string $transactionDate,
        ?string $description,
        ?string $notes,
        ?int $userId,
        ?string $idempotencyKey,
        ?float $stockBefore = null,
        ?float $stockAfter = null,
    ): StockTransaction {
        $stockBefore ??= round((float) $product->current_stock, 3);
        $stockAfter ??= $type === StockTransaction::TYPE_IN
            ? round($stockBefore + $quantity, 3)
            : round($stockBefore - $quantity, 3);

        return StockTransaction::create([
            'transaction_number' => $this->generateTransactionNumber($type),
            'product_id' => $product->id,
            'transaction_type' => $type,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'unit_price' => $unitPrice,
            'transaction_date' => $transactionDate,
            'description' => $description,
            'notes' => $notes,
            'created_by' => $userId,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    private function generateTransactionNumber(string $type): string
    {
        $prefix = $type === StockTransaction::TYPE_IN ? 'IN' : 'OUT';

        do {
            $number = sprintf(
                'TRX-%s-%s-%s',
                $prefix,
                now()->format('Ymd'),
                Str::upper(Str::random(6))
            );
        } while (StockTransaction::query()->where('transaction_number', $number)->exists());

        return $number;
    }

    private function formatQuantity(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');
    }
}
