<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Builds report queries so the JSON views and CSV exports always use the
 * exact same filters and aggregation rules.
 */
class ReportService
{
    public function inventoryQuery(array $filters): Builder
    {
        return Product::query()
            ->with(['category', 'unit'])
            ->where('is_active', true)
            ->when(! empty($filters['search']), function (Builder $query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function (Builder $query) use ($search) {
                    $query->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when(! empty($filters['category_id']), fn (Builder $query) => $query->where('category_id', $filters['category_id']))
            ->when(! empty($filters['stock_status']), function (Builder $query) use ($filters) {
                match ($filters['stock_status']) {
                    'out_of_stock' => $query->outOfStock(),
                    'low_stock' => $query->lowStock(),
                    'in_stock' => $query->whereColumn('current_stock', '>', 'minimum_stock'),
                };
            })
            ->orderBy('name');
    }

    public function lowStockQuery(array $filters): Builder
    {
        return Product::query()
            ->with(['category', 'unit'])
            ->where('is_active', true)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->when(! empty($filters['search']), function (Builder $query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function (Builder $query) use ($search) {
                    $query->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when(! empty($filters['category_id']), fn (Builder $query) => $query->where('category_id', $filters['category_id']))
            ->orderBy('current_stock')
            ->orderBy('name');
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function dateRange(array $filters): array
    {
        $from = ! empty($filters['date_from'])
            ? Carbon::parse($filters['date_from'])->startOfDay()
            : Carbon::today()->startOfMonth();

        $to = ! empty($filters['date_to'])
            ? Carbon::parse($filters['date_to'])->endOfDay()
            : Carbon::today()->endOfDay();

        return [$from, $to];
    }

    public function movementQuery(array $filters): Builder
    {
        return $this->movementBaseQuery($filters)
            ->with(['product.unit', 'createdBy'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');
    }

    public function movementSummary(array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $summary = $this->movementBaseQuery($filters)
            ->selectRaw("COALESCE(SUM(CASE WHEN transaction_type = 'in' THEN quantity ELSE 0 END), 0) as total_in")
            ->selectRaw("COALESCE(SUM(CASE WHEN transaction_type = 'out' THEN quantity ELSE 0 END), 0) as total_out")
            ->selectRaw('COUNT(*) as total_transactions')
            ->first();

        return [
            'total_in' => (float) ($summary->total_in ?? 0),
            'total_out' => (float) ($summary->total_out ?? 0),
            'total_transactions' => (int) ($summary->total_transactions ?? 0),
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
        ];
    }

    /**
     * Base movement query shared by the paginated report and its CSV export.
     */
    private function movementBaseQuery(array $filters): Builder
    {
        [$from, $to] = $this->dateRange($filters);

        return StockTransaction::query()
            ->where('transaction_date', '>=', $from)
            ->where('transaction_date', '<=', $to)
            ->when(! empty($filters['search']), function (Builder $query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function (Builder $query) use ($search) {
                    $query->where('transaction_number', 'like', "%{$search}%")
                        ->orWhereHas('product', function (Builder $query) use ($search) {
                            $query->where('sku', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when(! empty($filters['product_id']), fn (Builder $query) => $query->where('product_id', $filters['product_id']))
            ->when(! empty($filters['category_id']), function (Builder $query) use ($filters) {
                $query->whereHas('product', fn (Builder $query) => $query->where('category_id', $filters['category_id']));
            })
            ->when(! empty($filters['transaction_type']), fn (Builder $query) => $query->where('transaction_type', $filters['transaction_type']));
    }

    public function stockStatusLabel(string $status): string
    {
        return match ($status) {
            'out_of_stock' => 'Stok Habis',
            'low_stock' => 'Stok Menipis',
            default => 'Stok Aman',
        };
    }

    public function transactionTypeLabel(string $type): string
    {
        return $type === StockTransaction::TYPE_IN ? 'Barang Masuk' : 'Barang Keluar';
    }

    /**
     * Prefix spreadsheet formulas so exported CSV files cannot execute them.
     */
    public function csvSafe(mixed $value): string
    {
        $value = (string) $value;

        if ($value !== '' && preg_match('/^[=+\-@\t\r]/', $value)) {
            return "'".$value;
        }

        return $value;
    }
}
