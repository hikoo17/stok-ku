<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockTransactionIndexRequest;
use App\Http\Resources\StockTransactionResource;
use App\Models\StockTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockTransactionController extends Controller
{
    public function index(StockTransactionIndexRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $transactions = StockTransaction::query()
            ->with(['product.unit', 'createdBy'])
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($query) use ($search) {
                    $query->where('transaction_number', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($query) use ($search) {
                            $query->where('sku', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when(! empty($filters['product_id']), fn ($query) => $query->where('product_id', $filters['product_id']))
            ->when(! empty($filters['category_id']), function ($query) use ($filters) {
                $query->whereHas('product', fn ($query) => $query->where('category_id', $filters['category_id']));
            })
            ->when(! empty($filters['transaction_type']), fn ($query) => $query->where('transaction_type', $filters['transaction_type']))
            ->when(! empty($filters['date_from']), fn ($query) => $query->whereDate('transaction_date', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($query) => $query->whereDate('transaction_date', '<=', $filters['date_to']))
            ->orderBy('transaction_date', $filters['sort_direction'] ?? 'desc')
            ->orderBy('id', $filters['sort_direction'] ?? 'desc')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return StockTransactionResource::collection($transactions);
    }

    public function show(StockTransaction $stockTransaction): JsonResponse
    {
        $stockTransaction->load(['product.unit', 'createdBy']);

        return response()->json([
            'data' => new StockTransactionResource($stockTransaction),
        ]);
    }
}
