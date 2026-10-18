<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryReportRequest;
use App\Http\Requests\LowStockReportRequest;
use App\Http\Requests\StockMovementReportRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockTransactionResource;
use App\Services\ReportExportService;
use App\Services\ReportService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly ReportExportService $exports,
    ) {}

    public function inventory(InventoryReportRequest $request): AnonymousResourceCollection
    {
        $products = $this->reports->inventoryQuery($request->validated())
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return ProductResource::collection($products)->additional([
            'generated_at' => now()->toISOString(),
        ]);
    }

    public function stockMovements(StockMovementReportRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $transactions = $this->reports->movementQuery($filters)
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return StockTransactionResource::collection($transactions)->additional([
            'summary' => $this->reports->movementSummary($filters),
            'generated_at' => now()->toISOString(),
        ]);
    }

    public function lowStock(LowStockReportRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $baseQuery = $this->reports->lowStockQuery($filters);
        $total = (clone $baseQuery)->count();
        $outOfStock = (clone $baseQuery)->where('current_stock', '<=', 0)->count();

        $products = $baseQuery
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return ProductResource::collection($products)->additional([
            'summary' => [
                'total' => $total,
                'low_stock' => $total - $outOfStock,
                'out_of_stock' => $outOfStock,
            ],
            'generated_at' => now()->toISOString(),
        ]);
    }

    public function exportInventory(InventoryReportRequest $request): Response
    {
        return $this->exports->download('inventory', $request->validated());
    }

    public function exportStockMovements(StockMovementReportRequest $request): Response
    {
        return $this->exports->download('movements', $request->validated());
    }

    public function exportLowStock(LowStockReportRequest $request): Response
    {
        return $this->exports->download('low-stock', $request->validated());
    }
}
