<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Builds consistent CSV, PDF, and DOCX exports from the same report queries
 * and filters used by the on-screen report tables.
 */
class ReportExportService
{
    public function __construct(private readonly ReportService $reports) {}

    public function download(string $type, array $filters): Response
    {
        $format = $filters['format'] ?? 'csv';

        $report = match ($type) {
            'inventory' => $this->inventoryReport($filters),
            'movements' => $this->movementReport($filters),
            'low-stock' => $this->lowStockReport($filters),
            default => throw new \InvalidArgumentException('Jenis laporan tidak dikenal.'),
        };

        return match ($format) {
            'pdf' => $this->downloadPdf($report),
            'docx' => $this->downloadDocx($report),
            default => $this->downloadCsv($report),
        };
    }

    private function inventoryReport(array $filters): array
    {
        $products = $this->reports->inventoryQuery($filters)->get();

        $rows = $products->map(fn (Product $product) => [
            'sku' => $product->sku,
            'name' => $product->name,
            'category' => $product->category?->name ?? '-',
            'unit' => $product->unit?->abbreviation ?? '-',
            'current_stock' => (float) $product->current_stock,
            'minimum_stock' => (float) $product->minimum_stock,
            'purchase_price' => (float) $product->purchase_price,
            'selling_price' => (float) $product->selling_price,
            'stock_status' => $this->reports->stockStatusLabel($product->stock_status),
        ])->all();

        return [
            'title' => 'Laporan Stok Saat Ini',
            'file' => 'laporan-stok-saat-ini',
            'subtitle' => null,
            'filters' => $this->filterSummary($filters),
            'columns' => [
                ['key' => 'sku', 'label' => 'Kode Barang', 'type' => 'text'],
                ['key' => 'name', 'label' => 'Nama Barang', 'type' => 'text'],
                ['key' => 'category', 'label' => 'Kategori', 'type' => 'text'],
                ['key' => 'unit', 'label' => 'Satuan', 'type' => 'text'],
                ['key' => 'current_stock', 'label' => 'Stok Saat Ini', 'type' => 'quantity'],
                ['key' => 'minimum_stock', 'label' => 'Stok Minimum', 'type' => 'quantity'],
                ['key' => 'purchase_price', 'label' => 'Harga Beli (Rp)', 'type' => 'currency'],
                ['key' => 'selling_price', 'label' => 'Harga Jual (Rp)', 'type' => 'currency'],
                ['key' => 'stock_status', 'label' => 'Status Stok', 'type' => 'text'],
            ],
            'rows' => $rows,
            'totals' => [
                ['label' => 'Jumlah barang', 'value' => (string) count($rows)],
            ],
        ];
    }

    private function movementReport(array $filters): array
    {
        $transactions = $this->reports->movementQuery($filters)->get();
        $summary = $this->reports->movementSummary($filters);

        $rows = $transactions->map(fn ($transaction) => [
            'transaction_date' => $transaction->transaction_date,
            'transaction_number' => $transaction->transaction_number,
            'sku' => $transaction->product?->sku ?? '-',
            'name' => $transaction->product?->name ?? '-',
            'type' => $this->reports->transactionTypeLabel($transaction->transaction_type),
            'quantity' => (float) $transaction->quantity,
            'unit' => $transaction->product?->unit?->abbreviation ?? '-',
            'stock_before' => (float) $transaction->stock_before,
            'stock_after' => (float) $transaction->stock_after,
            'unit_price' => (float) $transaction->unit_price,
            'description' => $transaction->description ?: ($transaction->notes ?: '-'),
            'created_by' => $transaction->createdBy?->name ?? '-',
        ])->all();

        return [
            'title' => 'Laporan Pergerakan Stok',
            'file' => 'laporan-pergerakan-stok',
            'subtitle' => 'Periode: '
                .Carbon::parse($summary['date_from'])->translatedFormat('d F Y')
                .' s.d. '
                .Carbon::parse($summary['date_to'])->translatedFormat('d F Y'),
            'filters' => $this->filterSummary($filters),
            'columns' => [
                ['key' => 'transaction_date', 'label' => 'Tanggal', 'type' => 'datetime'],
                ['key' => 'transaction_number', 'label' => 'Referensi', 'type' => 'text'],
                ['key' => 'sku', 'label' => 'Kode Barang', 'type' => 'text'],
                ['key' => 'name', 'label' => 'Nama Barang', 'type' => 'text'],
                ['key' => 'type', 'label' => 'Jenis Transaksi', 'type' => 'text'],
                ['key' => 'quantity', 'label' => 'Jumlah', 'type' => 'quantity'],
                ['key' => 'unit', 'label' => 'Satuan', 'type' => 'text'],
                ['key' => 'stock_before', 'label' => 'Stok Sebelum', 'type' => 'quantity'],
                ['key' => 'stock_after', 'label' => 'Stok Sesudah', 'type' => 'quantity'],
                ['key' => 'unit_price', 'label' => 'Harga (Rp)', 'type' => 'currency'],
                ['key' => 'description', 'label' => 'Keterangan', 'type' => 'text'],
                ['key' => 'created_by', 'label' => 'Dicatat Oleh', 'type' => 'text'],
            ],
            'rows' => $rows,
            'totals' => [
                ['label' => 'Total barang masuk', 'value' => $this->displayQuantity($summary['total_in'])],
                ['label' => 'Total barang keluar', 'value' => $this->displayQuantity($summary['total_out'])],
                ['label' => 'Jumlah transaksi', 'value' => (string) $summary['total_transactions']],
            ],
        ];
    }

    private function lowStockReport(array $filters): array
    {
        $products = $this->reports->lowStockQuery($filters)->get();
        $lowStock = $products->filter(fn (Product $product) => (float) $product->current_stock > 0)->count();
        $outOfStock = $products->filter(fn (Product $product) => (float) $product->current_stock <= 0)->count();

        $rows = $products->map(fn (Product $product) => [
            'sku' => $product->sku,
            'name' => $product->name,
            'category' => $product->category?->name ?? '-',
            'unit' => $product->unit?->abbreviation ?? '-',
            'current_stock' => (float) $product->current_stock,
            'minimum_stock' => (float) $product->minimum_stock,
            'shortage' => max(0, (float) $product->minimum_stock - (float) $product->current_stock),
            'stock_status' => $this->reports->stockStatusLabel($product->stock_status),
            'purchase_price' => (float) $product->purchase_price,
        ])->all();

        return [
            'title' => 'Laporan Stok Menipis',
            'file' => 'laporan-stok-menipis',
            'subtitle' => null,
            'filters' => $this->filterSummary($filters),
            'columns' => [
                ['key' => 'sku', 'label' => 'Kode Barang', 'type' => 'text'],
                ['key' => 'name', 'label' => 'Nama Barang', 'type' => 'text'],
                ['key' => 'category', 'label' => 'Kategori', 'type' => 'text'],
                ['key' => 'unit', 'label' => 'Satuan', 'type' => 'text'],
                ['key' => 'current_stock', 'label' => 'Stok Saat Ini', 'type' => 'quantity'],
                ['key' => 'minimum_stock', 'label' => 'Stok Minimum', 'type' => 'quantity'],
                ['key' => 'shortage', 'label' => 'Kekurangan', 'type' => 'quantity'],
                ['key' => 'stock_status', 'label' => 'Status Stok', 'type' => 'text'],
                ['key' => 'purchase_price', 'label' => 'Harga Beli (Rp)', 'type' => 'currency'],
            ],
            'rows' => $rows,
            'totals' => [
                ['label' => 'Perlu perhatian', 'value' => (string) count($rows)],
                ['label' => 'Stok menipis', 'value' => (string) $lowStock],
                ['label' => 'Stok habis', 'value' => (string) $outOfStock],
            ],
        ];
    }

    private function downloadCsv(array $report): StreamedResponse
    {
        $filename = $report['file'].'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [$report['title']]);
            fputcsv($handle, ['Dibuat: '.now()->translatedFormat('d F Y H:i')]);

            if ($report['subtitle']) {
                fputcsv($handle, [$report['subtitle']]);
            }

            if ($report['filters'] !== []) {
                fputcsv($handle, ['Filter: '.implode(' | ', $report['filters'])]);
            }

            fputcsv($handle, []);
            fputcsv($handle, array_map(
                fn (array $column) => mb_strtoupper($column['label']),
                $report['columns'],
            ));

            foreach ($report['rows'] as $row) {
                fputcsv($handle, array_map(
                    fn (array $column) => $this->csvValue($column, $row[$column['key']] ?? ''),
                    $report['columns'],
                ));
            }

            if ($report['totals'] !== []) {
                fputcsv($handle, []);
                fputcsv($handle, ['RINGKASAN']);
                foreach ($report['totals'] as $total) {
                    fputcsv($handle, [$total['label'], $total['value']]);
                }
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Stok Ku']);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function downloadPdf(array $report): Response
    {
        $filename = $report['file'].'-'.now()->format('Y-m-d').'.pdf';

        return Pdf::loadView('reports.export-pdf', [
            'report' => $report,
            'headers' => $this->displayHeaders($report['columns']),
            'rows' => $this->displayRows($report['columns'], $report['rows']),
            'generatedAt' => now()->translatedFormat('d F Y H:i'),
        ])
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    private function downloadDocx(array $report): BinaryFileResponse
    {
        $filename = $report['file'].'-'.now()->format('Y-m-d').'.docx';
        $phpWord = new PhpWord;
        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'paperSize' => 'A4',
            'marginLeft' => 560,
            'marginRight' => 560,
            'marginTop' => 560,
            'marginBottom' => 560,
        ]);

        $section->addText('Stok Ku', [
            'name' => 'Arial',
            'size' => 9,
            'bold' => true,
            'color' => '4F46E5',
            'allCaps' => true,
            'spacing' => 80,
        ]);
        $section->addText($report['title'], [
            'name' => 'Arial',
            'size' => 18,
            'bold' => true,
            'color' => '0F172A',
        ]);

        $metaLine = 'Dibuat: '.now()->translatedFormat('d F Y H:i');
        if ($report['subtitle']) {
            $metaLine .= '  |  '.$report['subtitle'];
        }
        $section->addText($metaLine, [
            'name' => 'Arial',
            'size' => 9,
            'color' => '64748B',
        ]);

        if ($report['filters'] !== []) {
            $section->addText('Filter: '.implode(' | ', $report['filters']), [
                'name' => 'Arial',
                'size' => 9,
                'color' => '64748B',
            ]);
        }

        $section->addTextBreak(1);

        $table = $section->addTable([
            'borderSize' => 4,
            'borderColor' => 'CBD5E1',
            'cellMargin' => 60,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);

        $table->addRow();
        foreach ($this->displayHeaders($report['columns']) as $header) {
            $cell = $table->addCell(900, ['bgColor' => 'F1F5F9']);
            $cell->addText($header, [
                'name' => 'Arial',
                'size' => 8,
                'bold' => true,
                'color' => '334155',
            ]);
        }

        foreach ($this->displayRows($report['columns'], $report['rows']) as $row) {
            $table->addRow();
            foreach ($row as $value) {
                $cell = $table->addCell(900);
                $cell->addText($value, [
                    'name' => 'Arial',
                    'size' => 8,
                    'color' => '334155',
                ]);
            }
        }

        if ($report['totals'] !== []) {
            $section->addTextBreak(1);
            $section->addText('Ringkasan', [
                'name' => 'Arial',
                'size' => 10,
                'bold' => true,
                'color' => '0F172A',
            ]);
            foreach ($report['totals'] as $total) {
                $section->addText($total['label'].': '.$total['value'], [
                    'name' => 'Arial',
                    'size' => 9,
                    'color' => '475569',
                ]);
            }
        }

        $footer = $section->addFooter();
        $footer->addText(
            'Stok Ku — '.$report['title'].'  |  '.now()->translatedFormat('d F Y'),
            ['name' => 'Arial', 'size' => 8, 'color' => '94A3B8'],
            ['alignment' => 'center'],
        );

        $temporaryFile = tempnam(sys_get_temp_dir(), 'stok-ku-');
        @unlink($temporaryFile);
        $path = $temporaryFile.'.docx';

        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    private function filterSummary(array $filters): array
    {
        $summary = [];

        if (! empty($filters['category_id'])) {
            $category = Category::query()->find($filters['category_id']);
            if ($category) {
                $summary[] = 'Kategori: '.$category->name;
            }
        }

        if (! empty($filters['product_id'])) {
            $product = Product::query()->find($filters['product_id']);
            if ($product) {
                $summary[] = 'Barang: '.$product->sku.' - '.$product->name;
            }
        }

        if (! empty($filters['transaction_type'])) {
            $summary[] = 'Jenis: '.$this->reports->transactionTypeLabel($filters['transaction_type']);
        }

        if (! empty($filters['stock_status'])) {
            $summary[] = 'Status: '.$this->reports->stockStatusLabel($filters['stock_status']);
        }

        if (! empty($filters['search'])) {
            $summary[] = 'Kata kunci: '.$filters['search'];
        }

        return $summary;
    }

    /**
     * @param  array<int, array{key:string,label:string,type:string}>  $columns
     * @return array<int, string>
     */
    private function displayHeaders(array $columns): array
    {
        return array_map(fn (array $column) => mb_strtoupper($column['label']), $columns);
    }

    /**
     * @param  array<int, array{key:string,label:string,type:string}>  $columns
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<int, string>>
     */
    private function displayRows(array $columns, array $rows): array
    {
        return array_map(
            fn (array $row) => array_map(
                fn (array $column) => $this->displayValue($column, $row[$column['key']] ?? ''),
                $columns,
            ),
            $rows,
        );
    }

    private function csvValue(array $column, mixed $value): mixed
    {
        return match ($column['type']) {
            'currency' => $this->numeric($value, 2),
            'quantity' => $this->numeric($value, 3),
            'datetime' => $value ? Carbon::parse($value)->format('d/m/Y H:i') : '-',
            default => $this->reports->csvSafe((string) $value),
        };
    }

    private function displayValue(array $column, mixed $value): string
    {
        return match ($column['type']) {
            'currency' => 'Rp '.number_format((float) $value, 0, ',', '.'),
            'quantity' => $this->displayQuantity((float) $value),
            'datetime' => $value ? Carbon::parse($value)->format('d/m/Y H:i') : '-',
            default => (string) $value,
        };
    }

    private function displayQuantity(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');
    }

    private function numeric(mixed $value, int $decimals): string
    {
        return rtrim(rtrim(number_format((float) $value, $decimals, '.', ''), '0'), '.');
    }
}
