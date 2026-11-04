# Sổ tay tự học phiếu thu, công nợ và báo cáo — Vũ Trung Đức

## 0. Đích đến

Đức cần tạo được chứng từ đúng, tự tính được công nợ khách hàng/chủ xe/thuê lái, kiểm tra sổ chi tiết và xuất PDF hoặc Excel. Mọi số liệu công nợ phải lần được về chuyến hoàn tất hoặc chứng từ có ngày ghi nhận cụ thể.

## 1. Quy ước chung

- Chỉ chuyến `completed` và còn hoạt động mới tạo phát sinh công nợ.
- Ngày hoàn tất ưu tiên `actual_completed_at`; dữ liệu cũ chưa có giờ thực tế dùng `completed_at`.
- Khoảng báo cáo phải tính số dư đầu kỳ từ dữ liệu **trước** ngày bắt đầu lọc.
- Công nợ là báo cáo, không nhập/sửa trực tiếp trên trang Thu chi.
- Chứng từ khóa hoặc ngừng hoạt động không được sửa và không còn tác động theo quy tắc khóa/ngừng hoạt động của hệ thống.

## 2. Công thức công nợ đã chốt

| Đối tượng | Công nợ phát sinh | Đã thu/đã trả | Công thức số dư |
|---|---|---|---|
| Khách hàng | Cước xe công nhân hoàn tất + cước xe du lịch hoàn tất | Phiếu thu khách hàng | Nợ cũ + phát sinh − đã thu |
| Chủ xe/hãng vận tải | Tiền thuê xe công nhân/du lịch hoàn tất | Phiếu chi trả chủ xe | Nợ cũ + phát sinh − đã trả |
| Thuê lái | Tổng tiền thuê lái ở chuyến bật cờ thuê lái | Phiếu chi chung cùng tên lái thuê | Phát sinh − đã trả |

`partner_vehicle_cost` là nghĩa vụ thuê xe snapshot trên chuyến. `hired_driver_cost` là nghĩa vụ thuê lái snapshot trên chuyến. Không dùng một trong hai trường này thay cho chứng từ đã trả.

## 3. Phiếu thu khách hàng

Tại **Tài chính và nhân sự** → **Thu chi** → **Phiếu thu khách hàng**, nhập:

- Khách hàng;
- Loại thu;
- Ngày thu;
- Số tiền;
- Phương thức;
- Người nộp và diễn giải nếu có.

Khi mở sửa, ngày thu phải được đổ lại từ chứng từ. Trước khi lưu, kiểm tra số tiền không âm và khách hàng đúng với đối tượng thanh toán. Phiếu thu không phải là cước xe; nó chỉ giảm phần khách còn nợ.

## 4. Chi trả chủ xe và thuê lái

### Chi trả chủ xe

Tại tab **Chi trả chủ xe**, chọn đúng chủ xe/hãng vận tải, ngày chi, số tiền, phương thức và diễn giải. Chứng từ này giảm công nợ chủ xe, không tự thay đổi tiền thuê xe đã chốt trên chuyến.

### Thanh toán thuê lái

Tại tab **Phiếu chi chung**, chọn loại chi phù hợp và ghi chính xác `hired_driver_name`. Báo cáo thuê lái ghép theo tên này; sai chính tả/ký tự khác sẽ thành một đối tượng công nợ khác. Trước khi ghi chi, đối chiếu số tiền thuê lái của tất cả chuyến hoàn tất mang cùng tên.

## 5. Đọc báo cáo công nợ

Mở nhóm **Báo cáo**, không tìm Công nợ trong Finance. Các màn quan trọng:

- **Công nợ khách hàng**: tổng hợp và chi tiết theo khách hàng.
- **Công nợ chủ xe**: tổng hợp và chi tiết theo chủ xe/hãng vận tải.
- **Lợi nhuận xe**: có phần chi trả thuê lái; không tạo tab công nợ thuê lái riêng.

Trên báo cáo tổng hợp, kiểm tra các thành phần `nợ cũ`, `phát sinh`, `đã thu/đã trả`, `số dư cuối`. Khi mở chi tiết, số dư chạy phải bắt đầu bằng số dư đầu kỳ rồi cộng/trừ từng chứng từ/chuyến theo thứ tự ngày.

### Ví dụ tính tay khách hàng

Nợ cũ 2.000.000; trong kỳ có xe công nhân hoàn tất 2.500.000 và xe du lịch hoàn tất 6.000.000; đã lập hai phiếu thu tổng 4.000.000.

`Công nợ cuối = 2.000.000 + 2.500.000 + 6.000.000 − 4.000.000 = 6.500.000`.

Nếu không khớp, kiểm tra lần lượt: trạng thái/ngày hoàn tất chuyến, khách hàng trên chuyến, ngày/chủ thể/số tiền chứng từ, rồi dữ liệu trước ngày lọc.

## 6. Liên hệ với bảng lương lái xe

Chi tiết lương của tài xế công ty gồm:

`trách nhiệm + lương công nhân + lương du lịch - tạm ứng - lái xe thu khách + lái xe nộp công ty + lái xe tự chi`.

**Công ty thu** trên chuyến du lịch không thay đổi thực lĩnh. Báo cáo lương phải hiển thị riêng các khoản lái thu, lái nộp và lái tự chi để đối chiếu.

Kỳ lương một tháng có thể có Bản 1, Bản 2… Bản mới thay thế bản cũ trước thanh toán/khóa; báo cáo mặc định lấy `is_current = true`. Không dùng bản `superseded` để thanh toán, và không tạo bản mới nếu tháng đã có bản `paid` hoặc `locked`.

## 7. Xuất báo cáo

Chọn khoảng ngày và đối tượng trước khi xuất:

- **Xuất PDF** là định dạng chính, thay cho Word/DOCX. Tiêu đề, bảng, tổng cộng và chữ ký phải gần mẫu Word cũ; tất cả nhãn cột là tiếng Việt.
- **Xuất Excel** được giữ nguyên để lọc/tính toán thêm.

Các báo cáo phải kiểm tra: xe công nhân, xe du lịch, công nợ tổng hợp/chi tiết, bảng lương tổng hợp/chi tiết và lợi nhuận xe. Không hướng dẫn hoặc kiểm thử luồng xuất Word nữa.

## 8. Kịch bản kiểm thử thủ công

1. Tạo một Phiếu thu khách hàng, sửa lại và kiểm tra Ngày thu không trống.
2. Tạo một Chi trả chủ xe và một Phiếu chi chung gắn đúng tên lái thuê.
3. Mở công nợ khách hàng/chủ xe đúng khoảng ngày; tính tay ít nhất một dòng.
4. Mở drill-down, kiểm tra chứng từ trước kỳ được đưa vào số dư đầu kỳ.
5. Mở Bảng lương lái xe, xem bản hiện hành và ba khoản đối soát du lịch.
6. Xuất PDF tổng hợp và chi tiết; kiểm tra tiêu đề/cột tiếng Việt. Xuất Excel lần nữa để chắc không bị ảnh hưởng.

## 9. Mốc source để debug

| Việc | Thành phần chính |
|---|---|
| Chứng từ tài chính | `app/Modules/Finance`, `src/modules/finance` |
| Công thức công nợ | `app/Modules/Other/Services/DebtReportService.php` |
| Kỳ lương, bản kỳ và thực lĩnh | `app/Modules/DriverPayroll/Services/DriverPayrollService.php` |
| PDF | `RestoredReportController.php`, `resources/views/reports/pdf.blade.php` |

Thứ tự truy vết: bộ lọc báo cáo → dòng tổng hợp → dòng chi tiết → chuyến/chứng từ nguồn. Không sửa số dư báo cáo bằng tay.
