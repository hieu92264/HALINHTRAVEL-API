# Quy trình nghiệp vụ đầu-cuối

## 1. Từ yêu cầu thuê xe đến điều hành chuyến

```text
Khách hàng và danh mục sẵn sàng
        ↓
Tạo yêu cầu thuê xe
        ↓
Kiểm tra năng lực
        ↓
Lập và gửi báo giá
        ↓
Khách chấp nhận báo giá
        ↓
Tạo, rồi kích hoạt hợp đồng theo chuyến
        ↓
Lịch chuyến được tạo
        ↓
Phân công xe + tài xế → Phát hành lệnh → Xác nhận phân công
        ↓
Tài xế bắt đầu và báo cáo → Điều hành xác nhận hoàn tất
```

Điểm kiểm soát:

- Trước báo giá và trước kích hoạt hợp đồng theo chuyến, kiểm tra năng lực phải đủ.
- Hợp đồng theo chuyến chỉ tạo từ báo giá đã duyệt và yêu cầu thuê xe đã chấp nhận.
- Không phát hành lệnh trước khi lịch đã có phân công hiện hành.

## 2. Hợp đồng nguyên tắc chạy định kỳ

```text
Tạo hợp đồng nguyên tắc
        ↓
Kích hoạt hợp đồng
        ↓
Tạo quy tắc lịch cố định theo thứ/giờ/ca
        ↓
Sinh lịch chuyến theo khoảng ngày
        ↓
Điều hành phân công và phát hành lệnh cho từng lịch
```

Sau khi đã sinh lịch từ một quy tắc, quy tắc không còn sửa được. Kiểm tra ngày chạy, giờ đón/trả, xe và tài xế mặc định trước khi sinh lịch.

## 3. Chốt một chuyến điều xe

```text
Lệnh đã phân công
        ↓
Tài xế nhập giờ và ODO bắt đầu
        ↓
Lệnh đang chạy
        ↓
Tài xế nhập giờ/ODO kết thúc, quãng đường và giờ chờ
        ↓
Chờ điều hành xác nhận
        ↓
Trả báo cáo để bổ sung  hoặc  Xác nhận hoàn tất
        ↓
Chuyến hoàn tất, ODO xe được cập nhật
```

Khi chốt, điều hành nhập doanh thu khách hàng và các chi phí ngoài nếu có. Đây là dữ liệu quan trọng cho báo cáo chuyến và lợi nhuận.

## 4. Thu chi, công nợ và lương

```text
Hợp đồng / lệnh điều xe hoàn tất
        ↓
Ghi nhận phiếu thu, chi phí, chi trả đối tác
        ↓
Đối soát công nợ và khóa chứng từ khi đã chốt
        ↓
Xác nhận tạm ứng và công chuyến
        ↓
Tạo kỳ lương → Tính → Duyệt → Đã chi → Chốt lương
        ↓
Xem báo cáo tài chính, lương và lợi nhuận
```

Không khóa chứng từ hoặc chốt lương trước khi hoàn tất đối soát, vì các thao tác này làm dữ liệu không thể thay đổi tiếp trong hệ thống.
