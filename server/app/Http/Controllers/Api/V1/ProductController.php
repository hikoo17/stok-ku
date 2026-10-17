<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function index(ProductIndexRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $products = Product::query()
            ->with(['category', 'unit'])
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($query) use ($search) {
                    $query->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when(! empty($filters['category_id']), fn ($query) => $query->where('category_id', $filters['category_id']))
            ->when(($filters['status'] ?? 'all') !== 'all', function ($query) use ($filters) {
                $query->where('is_active', ($filters['status'] ?? 'all') === 'active');
            })
            ->when(! empty($filters['stock_status']), function ($query) use ($filters) {
                match ($filters['stock_status']) {
                    'out_of_stock' => $query->outOfStock(),
                    'low_stock' => $query->lowStock(),
                    'in_stock' => $query->whereColumn('current_stock', '>', 'minimum_stock'),
                };
            })
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_direction'] ?? 'desc')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function options(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
            'include_inactive' => ['nullable', 'boolean'],
        ]);

        $products = Product::query()
            ->when(! $request->boolean('include_inactive'), fn ($query) => $query->active())
            ->with(['category', 'unit'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search'));
                $query->where(function ($query) use ($search) {
                    $query->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit($request->integer('limit', 200))
            ->get();

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $openingStock = (float) ($data['opening_stock'] ?? 0);
        unset($data['opening_stock']);

        $data['sku'] = trim($data['sku']);
        $data['current_stock'] = 0;
        $data['is_active'] = $data['is_active'] ?? true;

        $product = DB::transaction(function () use ($data, $openingStock, $request) {
            $product = Product::create($data);

            if ($openingStock > 0) {
                $this->inventory->recordOpeningStock($product, $openingStock, $request->user()->id);
            }

            return $product;
        });

        $product->refresh()->load(['category', 'unit']);

        return response()->json([
            'message' => 'Barang berhasil ditambahkan.',
            'data' => new ProductResource($product),
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'unit']);

        return response()->json([
            'data' => new ProductResource($product),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();
        $data['sku'] = trim($data['sku']);

        $product->update($data);

        return response()->json([
            'message' => 'Barang berhasil diperbarui.',
            'data' => new ProductResource($product->refresh()->load(['category', 'unit'])),
        ]);
    }

    /**
     * Archive the product instead of deleting it so history is preserved.
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->update(['is_active' => false]);

        return response()->json([
            'message' => 'Barang berhasil diarsipkan.',
            'data' => new ProductResource($product->refresh()->load(['category', 'unit'])),
        ]);
    }

    public function restore(Product $product): JsonResponse
    {
        $product->update(['is_active' => true]);

        return response()->json([
            'message' => 'Barang berhasil diaktifkan kembali.',
            'data' => new ProductResource($product->refresh()->load(['category', 'unit'])),
        ]);
    }
}
