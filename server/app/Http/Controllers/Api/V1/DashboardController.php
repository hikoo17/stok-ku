<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockTransactionResource;
use App\Models\Product;
use App\Models\StockTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $activeProducts = Product::query()->where('is_active', true);

        $totalProducts = (clone $activeProducts)->count();
        $totalStock = (float) (clone $activeProducts)->sum('current_stock');
        $lowStockCount = (clone $activeProducts)->lowStock()->count();
        $outOfStockCount = (clone $activeProducts)->outOfStock()->count();

        $recentIn = StockTransaction::query()
            ->incoming()
            ->with(['product.unit', 'createdBy'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $recentOut = StockTransaction::query()
            ->outgoing()
            ->with(['product.unit', 'createdBy'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $lowStockProducts = Product::query()
            ->where('is_active', true)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->with(['category', 'unit'])
            ->orderBy('current_stock')
            ->limit(5)
            ->get();

        $chartStart = Carbon::today()->subDays(6)->startOfDay();

        $movements = StockTransaction::query()
            ->where('transaction_date', '>=', $chartStart)
            ->selectRaw('DATE(transaction_date) as movement_date, transaction_type, SUM(quantity) as total_quantity')
            ->groupBy('movement_date', 'transaction_type')
            ->get();

        $chart = collect(range(6, 0))
            ->map(function (int $daysAgo) use ($movements) {
                $date = Carbon::today()->subDays($daysAgo);

                return [
                    'date' => $date->toDateString(),
                    'label' => $date->translatedFormat('d M'),
                    'stock_in' => (float) $movements
                        ->where('movement_date', $date->toDateString())
                        ->where('transaction_type', StockTransaction::TYPE_IN)
                        ->sum('total_quantity'),
                    'stock_out' => (float) $movements
                        ->where('movement_date', $date->toDateString())
                        ->where('transaction_type', StockTransaction::TYPE_OUT)
                        ->sum('total_quantity'),
                ];
            })
            ->values();

        return response()->json([
            'data' => [
                'summary' => [
                    'total_products' => $totalProducts,
                    'total_stock' => $totalStock,
                    'low_stock' => $lowStockCount,
                    'out_of_stock' => $outOfStockCount,
                ],
                'recent_stock_in' => StockTransactionResource::collection($recentIn),
                'recent_stock_out' => StockTransactionResource::collection($recentOut),
                'low_stock_products' => ProductResource::collection($lowStockProducts),
                'chart' => $chart,
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }
}
