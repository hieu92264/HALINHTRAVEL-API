<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 14mm 8mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            color: #111
        }

        h1 {
            text-align: center;
            font-size: 15px;
            margin: 0 0 5px
        }

        .letterhead { width: 100%; margin-bottom: 12px; font-size: 9px; font-weight: bold; }
        .letterhead .company { float: left; width: 48%; }
        .letterhead .nation { float: right; width: 48%; text-align: center; }
        .clear { clear: both; }
        .legacy-company { width: 35%; margin: 4px 0 12px; font-family: DejaVu Serif, serif; font-size: 10px; font-weight: bold; text-align: center; }

        .period {
            text-align: center;
            margin-bottom: 10px
        }

        table {
            border-collapse: collapse;
            width: 100%
        }

        th,
        td {
            border: 1px solid #999;
            padding: 4px 3px;
            vertical-align: top
        }

        th {
            background: #1f4e78;
            color: #fff;
            text-align: center
        }

        td.number {
            text-align: right
        }

        .totals {
            margin: 10px 0;
            font-weight: bold
        }

        .total-row td {
            background: #eaf1f8;
            font-weight: bold;
        }

        .compact-table {
            font-size: 6.3px;
        }

        .compact-table th,
        .compact-table td {
            padding: 3px 2px;
        }

        .signatures {
            margin-top: 28px;
            width: 100%;
            text-align: center
        }

        .signatures td {
            border: 0;
            width: 33%;
            font-weight: bold
        }

        .signatures .director {
            text-align: center;
        }

        .signatures .accountant {
            text-align: center;
        }

        .signatures .preparer {
            text-align: right;
        }

        .signature-date td {
            padding-bottom: 12px;
            font-style: italic;
            font-weight: normal;
        }

        .signature-date .director {
            text-align: left;
        }
    </style>
</head>

<body>
    @if (!empty($pdf['legacy_trip_layout']))
        <div class="legacy-company">{{ config('reports.company_name', config('app.name')) }}</div>
    @else
        <div class="letterhead">
            <div class="company">{{ config('reports.company_name', config('app.name')) }}</div>
            <div class="nation">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM<br><span style="font-style: italic; font-weight: normal">Độc lập - Tự do - Hạnh phúc</span></div>
            <div class="clear"></div>
        </div>
    @endif
    <h1>{{ $title }}</h1>
    <p class="period">{{ $period }}</p>
    @if (!empty($report['subject_name']))
        <p><strong>Đối tượng:</strong> {{ $report['subject_name'] }}</p>
    @endif
    @php($rows = $pdf['rows']) @php($columns = $pdf['columns']) @php($totals = $pdf['totals'] ?? [])
    <table class="{{ count($columns) > 10 ? 'compact-table' : '' }}">
        <thead>
            <tr>
                @foreach ($columns as $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                @foreach ($columns as $key => $label)
                        <td class="{{ preg_match('/amount|balance|wage|cost|revenue|salary|commission|profit|debit|credit|received|paid|remitted|receivable|allowance/', $key) ? 'number' : '' }}">{{ $row[$key] ?? '' }}
                        </td>
                    @endforeach
                </tr>
            @empty<tr>
                    <td colspan="{{ max(1, count($columns)) }}">Không có dữ liệu</td>
                </tr>
            @endforelse
            @if (!empty($totals))
                <tr class="total-row">
                    @foreach ($columns as $key => $label)
                        <td class="{{ preg_match('/amount|balance|wage|cost|revenue|salary|commission|profit|debit|credit|received|paid|remitted|receivable|allowance/', $key) ? 'number' : '' }}">
                            {{ $loop->first ? 'Tổng cộng' : ($totals[$key] ?? '') }}
                        </td>
                    @endforeach
                </tr>
            @endif
        </tbody>
    </table>
    <table class="signatures">
        <tr class="signature-date">
            <td class="director">{{ $signaturePeriod }}</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td class="director">GIÁM ĐỐC</td>
            <td class="accountant">KẾ TOÁN</td>
            <td class="preparer">NGƯỜI LẬP</td>
        </tr>
    </table>
</body>

</html>
