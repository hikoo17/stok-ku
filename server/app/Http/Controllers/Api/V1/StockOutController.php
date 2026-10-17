<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockOutRequest;
use App\Http\Resources\StockTransactionResource;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;

class StockOutController extends Controller
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function store(StockOutRequest $request): JsonResponse
    {
        $transaction = $this->inventory->recordStockOut(
            $request->validated(),
            $request->user()->id,
        );

        $transaction->load(['product.unit', 'createdBy']);

        return response()->json([
            'message' => 'Transaksi barang keluar berhasil disimpan.',
            'data' => new StockTransactionResource($transaction),
        ], 201);
    }
}
