<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryReportRequest;
use App\Http\Requests\LowStockReportRequest;
use App\Http\Requests\StockMovementReportRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockTransactionResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\ReportService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

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

    public function exportInventory(InventoryReportRequest $request): StreamedResponse
    {
        $filters = $request->validated();
        $query = $this->reports->inventoryQuery($filters);

        return $this->streamCsv('laporan-stok-saat-ini', function ($handle) use ($query, $filters) {
            $this->writeMetadata($handle, 'Laporan Stok Saat Ini', $filters);

            fputcsv($handle, [
                'Kode Produk',
                'Nama Barang',
                'Kategori',
                'Satuan',
                'Stok Saat Ini',
                'Stok Minimum',
                'Harga Beli (Rp)',
                'Harga Jual (Rp)',
                'Status Stok',
            ]);

            foreach ($query->lazy(500) as $product) {
                fputcsv($handle, [
                    $this->reports->csvSafe($product->sku),
                    $this->reports->csvSafe($product->name),
                    $this->reports->csvSafe($product->category?->name ?? '-'),
                    $this->reports->csvSafe($product->unit?->abbreviation ?? '-'),
                    (float) $product->current_stock,
                    (float) $product->minimum_stock,
                    (float) $product->purchase_price,
                    (float) $product->selling_price,
                    $this->reports->stockStatusLabel($product->stock_status),
                ]);
            }
        });
    }

    public function exportStockMovements(StockMovementReportRequest $request): StreamedResponse
    {
        $filters = $request->validated();
        $query = $this->reports->movementQuery($filters);
        $summary = $this->reports->movementSummary($filters);

        return $this->streamCsv('laporan-pergerakan-stok', function ($handle) use ($query, $filters, $summary) {
            $this->writeMetadata(
                $handle,
                'Laporan Pergerakan Stok',
                $filters,
                'Periode: '.$this->formatDate($summary['date_from']).' s.d. '.$this->formatDate($summary['date_to']),
                'Total barang masuk: '.$this->formatQuantity($summary['total_in']).' | Total barang keluar: '.$this->formatQuantity($summary['total_out']),
            );

            fputcsv($handle, [
                'Tanggal',
                'Referensi',
                'Kode Produk',
                'Nama Barang',
                'Jenis Transaksi',
                'Jumlah',
                'Satuan',
                'Stok Sebelum',
                'Stok Sesudah',
                'Harga (Rp)',
                'Keterangan',
                'Dicatat Oleh',
            ]);

            foreach ($query->lazy(500) as $transaction) {
                fputcsv($handle, [
                    $transaction->transaction_date?->format('d/m/Y H:i'),
                    $this->reports->csvSafe($transaction->transaction_number),
                    $this->reports->csvSafe($transaction->product?->sku ?? '-'),
                    $this->reports->csvSafe($transaction->product?->name ?? '-'),
                    $this->reports->transactionTypeLabel($transaction->transaction_type),
                    (float) $transaction->quantity,
                    $this->reports->csvSafe($transaction->product?->unit?->abbreviation ?? '-'),
                    (float) $transaction->stock_before,
                    (float) $transaction->stock_after,
                    (float) $transaction->unit_price,
                    $this->reports->csvSafe($transaction->description ?? $transaction->notes ?? '-'),
                    $this->reports->csvSafe($transaction->createdBy?->name ?? '-'),
                ]);
            }
        });
    }

    public function exportLowStock(LowStockReportRequest $request): StreamedResponse
    {
        $filters = $request->validated();
        $query = $this->reports->lowStockQuery($filters);

        return $this->streamCsv('laporan-stok-menipis', function ($handle) use ($query, $filters) {
            $this->writeMetadata($handle, 'Laporan Stok Menipis', $filters);

            fputcsv($handle, [
                'Kode Produk',
                'Nama Barang',
                'Kategori',
                'Satuan',
                'Stok Saat Ini',
                'Stok Minimum',
                'Kekurangan',
                'Status Stok',
                'Harga Beli (Rp)',
            ]);

            foreach ($query->lazy(500) as $product) {
                $shortage = max(0, (float) $product->minimum_stock - (float) $product->current_stock);

                fputcsv($handle, [
                    $this->reports->csvSafe($product->sku),
                    $this->reports->csvSafe($product->name),
                    $this->reports->csvSafe($product->category?->name ?? '-'),
                    $this->reports->csvSafe($product->unit?->abbreviation ?? '-'),
                    (float) $product->current_stock,
                    (float) $product->minimum_stock,
                    $shortage,
                    $this->reports->stockStatusLabel($product->stock_status),
                    (float) $product->purchase_price,
                ]);
            }
        });
    }

    private function streamCsv(string $filename, callable $writer): StreamedResponse
    {
        $date = now()->format('Y-m-d');

        return response()->streamDownload(function () use ($writer) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM keeps Indonesian characters readable in spreadsheet apps.
            fwrite($handle, "\xEF\xBB\xBF");
            $writer($handle);
            fclose($handle);
        }, "{$filename}-{$date}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function writeMetadata($handle, string $title, array $filters, string ...$extraLines): void
    {
        fputcsv($handle, [$title]);
        fputcsv($handle, ['Dibuat: '.now()->translatedFormat('d F Y H:i')]);

        if (! empty($filters['category_id'])) {
            $category = Category::query()->find($filters['category_id']);
            if ($category) {
                fputcsv($handle, ['Kategori: '.$category->name]);
            }
        }

        if (! empty($filters['product_id'])) {
            $product = Product::query()->find($filters['product_id']);
            if ($product) {
                fputcsv($handle, ['Barang: '.$product->sku.' - '.$product->name]);
            }
        }

        if (! empty($filters['transaction_type'])) {
            fputcsv($handle, ['Jenis transaksi: '.$this->reports->transactionTypeLabel($filters['transaction_type'])]);
        }

        if (! empty($filters['stock_status'])) {
            fputcsv($handle, ['Status stok: '.$this->reports->stockStatusLabel($filters['stock_status'])]);
        }

        if (! empty($filters['search'])) {
            fputcsv($handle, ['Kata kunci: '.$filters['search']]);
        }

        foreach ($extraLines as $line) {
            fputcsv($handle, [$line]);
        }

        fputcsv($handle, []);
    }

    private function formatDate(string $date): string
    {
        return Carbon::parse($date)->translatedFormat('d F Y');
    }

    private function formatQuantity(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');
    }
}
