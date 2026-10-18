<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    <style>
        @page {
            margin: 24px 28px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #334155;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9px;
            line-height: 1.45;
        }

        .brand {
            color: #4f46e5;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }

        h1 {
            margin: 5px 0 4px;
            color: #0f172a;
            font-size: 19px;
            line-height: 1.2;
        }

        .meta {
            margin-top: 2px;
            color: #64748b;
            font-size: 8.5px;
        }

        .section-label {
            margin: 18px 0 6px;
            color: #0f172a;
            font-size: 10px;
            font-weight: bold;
        }

        table {
            width: 100%;
            margin-top: 12px;
            border-collapse: collapse;
            page-break-inside: auto;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        th {
            border: 1px solid #cbd5e1;
            background: #f1f5f9;
            color: #334155;
            font-size: 7.6px;
            font-weight: bold;
            letter-spacing: 0.35px;
            padding: 6px 5px;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            border: 1px solid #e2e8f0;
            padding: 5px;
            vertical-align: top;
            word-wrap: break-word;
        }

        tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .totals {
            margin-top: 12px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 9px 12px;
            width: 45%;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 2px 0;
        }

        .total-label {
            color: #64748b;
        }

        .total-value {
            color: #0f172a;
            font-weight: bold;
        }

        .footer {
            margin-top: 18px;
            border-top: 1px solid #e2e8f0;
            padding-top: 7px;
            color: #94a3b8;
            font-size: 7.5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="brand">Stok Ku</div>
    <h1>{{ $report['title'] }}</h1>
    <div class="meta">Dibuat: {{ $generatedAt }}</div>

    @if ($report['subtitle'])
        <div class="meta">{{ $report['subtitle'] }}</div>
    @endif

    @if ($report['filters'] !== [])
        <div class="meta">Filter: {{ implode(' | ', $report['filters']) }}</div>
    @endif

    <table>
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" style="text-align:center;color:#64748b;">
                        Tidak ada data untuk filter yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($report['totals'] !== [])
        <div class="totals">
            @foreach ($report['totals'] as $total)
                <div class="total-row">
                    <span class="total-label">{{ $total['label'] }}</span>
                    <span class="total-value">{{ $total['value'] }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <div class="footer">
        Stok Ku &mdash; {{ $report['title'] }} &middot; {{ $generatedAt }}
    </div>
</body>
</html>
