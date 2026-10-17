<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UnitController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,inactive,all'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $units = Unit::query()
            ->withCount('products')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search'));
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('abbreviation', 'like', "%{$search}%");
                });
            })
            ->when($request->string('status', 'all')->toString() !== 'all', function ($query) use ($request) {
                $query->where('is_active', $request->string('status')->toString() === 'active');
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return UnitResource::collection($units);
    }

    public function options(): AnonymousResourceCollection
    {
        return UnitResource::collection(
            Unit::query()->active()->orderBy('name')->get()
        );
    }

    public function store(UnitRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['is_active'] = $data['is_active'] ?? true;

        $unit = Unit::create($data);

        return response()->json([
            'message' => 'Satuan berhasil ditambahkan.',
            'data' => new UnitResource($unit->loadCount('products')),
        ], 201);
    }

    public function show(Unit $unit): JsonResponse
    {
        return response()->json([
            'data' => new UnitResource($unit->loadCount('products')),
        ]);
    }

    public function update(UnitRequest $request, Unit $unit): JsonResponse
    {
        $unit->update($request->validated());

        return response()->json([
            'message' => 'Satuan berhasil diperbarui.',
            'data' => new UnitResource($unit->loadCount('products')),
        ]);
    }

    public function destroy(Unit $unit): JsonResponse
    {
        if ($unit->products()->exists()) {
            return response()->json([
                'message' => 'Satuan tidak dapat dihapus karena masih digunakan oleh produk.',
            ], 422);
        }

        $unit->delete();

        return response()->json([
            'message' => 'Satuan berhasil dihapus.',
        ]);
    }
}
