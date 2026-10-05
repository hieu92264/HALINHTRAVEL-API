<p>Xin chào {{ $quotation->customer->contact_name ?: $quotation->customer->name }},</p>
<p>Halinh Travel gửi đến Quý khách báo giá <strong>{{ $quotation->quotation_no }}</strong>.</p>
<ul>
    @foreach ($quotation->items as $item)
        <li>{{ $item->vehicleType->name }} × {{ $item->quantity }}: {{ number_format((float) $item->amount, 0, ',', '.') }} đ</li>
    @endforeach
</ul>
<p><strong>Tổng cộng: {{ number_format((float) $quotation->total_amount, 0, ',', '.') }} đ</strong></p>
@if ($quotation->valid_until)
    <p>Báo giá có hiệu lực đến hết ngày {{ $quotation->valid_until->format('d/m/Y') }}.</p>
@endif
@if ($quotation->payment_terms)
    <p>Điều khoản thanh toán: {{ $quotation->payment_terms }}</p>
@endif
<p><a href="{{ $responseUrl }}">Xem và phản hồi báo giá</a></p>
<p>Trân trọng,<br>Halinh Travel</p>
