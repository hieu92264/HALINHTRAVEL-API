<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><style>
@page { margin: 14mm 8mm; } body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
.company { text-align: center; font-weight: bold; font-size: 11px; margin-bottom: 8px; } h1 { text-align: center; font-size: 15px; margin: 0 0 14px; }
table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #999; padding: 4px 3px; vertical-align: top; } th { background: #1f4e78; color: #fff; text-align: center; } td.amount { text-align: right; }
.preparer { margin-top: 28px; text-align: right; font-weight: bold; }
</style></head><body>
<div class="company">{{ config('reports.company_name', config('app.name')) }}</div><h1>{{ $title }}</h1>
<table><thead><tr>@foreach($columns as $label)<th>{{ $label }}</th>@endforeach</tr></thead><tbody>
@forelse($rows as $row)<tr>@foreach($columns as $key => $label)<td class="{{ $key === 'amount' ? 'amount' : '' }}">{{ $row[$key] ?? '' }}</td>@endforeach</tr>
@empty<tr><td colspan="{{ count($columns) }}">Không có dữ liệu</td></tr>@endforelse
</tbody></table><div class="preparer">NGƯỜI LẬP</div></body></html>
