<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockInRequest;
use App\Http\Resources\StockTransactionResource;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;

class StockInController extends Controller
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function store(StockInRequest $request): JsonResponse
    {
        $transaction = $this->inventory->recordStockIn(
            $request->validated(),
            $request->user()->id,
        );

        $transaction->load(['product.unit', 'createdBy']);

        return response()->json([
            'message' => 'Transaksi barang masuk berhasil disimpan.',
            'data' => new StockTransactionResource($transaction),
        ], 201);
    }
}
