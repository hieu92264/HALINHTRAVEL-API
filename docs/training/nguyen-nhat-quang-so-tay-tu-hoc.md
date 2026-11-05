# Sổ tay tự học danh mục, thu chi và lợi nhuận xe — Nguyễn Nhật Quang

## 0. Mục tiêu

Quang cần quản lý đúng danh mục nền, lập chứng từ thu/chi, hiểu sự khác nhau giữa chi phí chuyến và chứng từ thanh toán, rồi kiểm tra báo cáo lợi nhuận xe. Thuật ngữ sử dụng từ tháng 11/2026 là **Thu chi**, **Phiếu thu khách hàng** và **Chi trả chủ xe**.

## 1. Bản đồ nghiệp vụ

```text
Danh mục khách hàng / hãng vận tải / loại xe / xe công ty / tài xế
                │
                ├── Chuyến hoàn tất ──> doanh thu, chi phí thuê xe, lương lái
                │
                ├── Phiếu thu khách hàng ──> giảm công nợ khách hàng
                ├── Chi trả chủ xe ────────> giảm công nợ chủ xe
                └── Phiếu chi chung ───────> chi phí chung hoặc thanh toán thuê lái
                                                    │
                                              Báo cáo lợi nhuận xe
```

Chỉ chuyến `completed` và chứng từ còn hoạt động mới đi vào các báo cáo tài chính. Không tự đổi lịch sử chuyến khi danh mục hoặc giá được sửa sau đó.

## 2. Danh mục phải hiểu

| Danh mục | Dùng ở đâu | Quy tắc chính |
|---|---|---|
| Khách hàng | Chuyến, phiếu thu, công nợ | Có số dư đầu kỳ; không nhầm với người nộp |
| Hãng vận tải/chủ xe | Xe đối tác theo chuyến, chi trả chủ xe, công nợ | Là đối tượng phải trả tiền thuê xe |
| Xe công ty | Chuyến công nhân/du lịch | Không có chủ xe đối tác |
| Xe đối tác | Chuyến công nhân/du lịch | Không thuộc danh mục Xe; bắt buộc hãng, loại xe, biển số tay và tiền thuê |
| Tài xế công ty | Chuyến, công, lương | Có tài xế mặc định ở Xe; thuê lái là dữ liệu theo chuyến |
| Tuyến | Xe công nhân | Chứa khách hàng, ca, điểm/giờ đón trả và sáu dòng giá |
| Loại xe và bảng giá tuyến | Xe, Tuyến, lương du lịch | Giá cố định theo tuyến × loại xe; snapshot vào chuyến |

Xe trong danh mục luôn là xe công ty và phải có Loại xe, tài xế mặc định đang hoạt động. Sáu Loại xe chuẩn là `Xe04`, `Xe07`, `Xe16`, `Xe29`, `Xe34`, `Xe45`.

## 3. Thu chi: ba tab duy nhất

Trang `/finance` có ba khu vực nghiệp vụ:

1. **Phiếu thu khách hàng** — ghi nhận tiền khách đã thanh toán.
2. **Phiếu chi chung** — các khoản chi chung, bao gồm thanh toán thuê lái khi khai báo tên lái thuê.
3. **Chi trả chủ xe** — chứng từ trả tiền cho chủ xe/hãng vận tải.

Không còn tab **Công nợ** ở màn Thu chi. Công nợ chỉ đọc trong nhóm **Báo cáo** để tránh nhầm báo cáo tính toán với chứng từ được phép nhập/sửa.

### Phiếu thu khách hàng

Thông tin tối thiểu: khách hàng, loại thu, ngày thu, số tiền, phương thức; người nộp và diễn giải giúp đối chiếu. Khi sửa chứng từ phải giữ đúng `received_at`/Ngày thu, không để trường ngày rỗng. Phiếu thu làm giảm công nợ khách hàng, không sửa cước snapshot của chuyến.

### Phiếu chi chung và thuê lái

Nếu chi để thanh toán thuê lái, bắt buộc ghi `hired_driver_name` đúng bằng tên lái thuê trên chuyến. Đây là số đã trả; chi phí thuê lái trên chuyến (`hired_driver_cost`) là nghĩa vụ phát sinh. Hai số khác nhau và phải được đối soát theo cùng tên lái thuê.

### Chi trả chủ xe

Thông tin tối thiểu: chủ xe/hãng vận tải, ngày chi, số tiền, phương thức, diễn giải. Chứng từ này giảm công nợ chủ xe; không thay đổi `partner_vehicle_cost` snapshot của chuyến đã hoàn tất.

## 4. Công nợ và lợi nhuận

Các công thức báo cáo:

| Báo cáo | Phát sinh | Đã thanh toán/thu | Số dư |
|---|---|---|---|
| Khách hàng | Cước công nhân + cước du lịch lịch sử còn liên kết khách hàng | Phiếu thu khách hàng | Nợ cũ + phát sinh − đã thu |
| Chủ xe | Tiền thuê xe công nhân + du lịch hoàn tất | Chi trả chủ xe | Nợ cũ + phát sinh − đã trả |
| Thuê lái | Tiền thuê lái ở chuyến có cờ thuê lái | Phiếu chi chung cùng tên lái thuê | Phát sinh − đã trả |

Báo cáo lợi nhuận xe dùng cước và các chi phí đã snapshot trên chuyến. Báo cáo chi trả lái xe nằm trong **Lợi nhuận xe**, không có tab công nợ thuê lái riêng trên Finance.

Xe du lịch mới dùng tên khách hàng nhập tay. Không tự tạo khách hàng danh mục hoặc tự gán `customer_id` chỉ để đưa tên đó vào công nợ; đối soát bằng tên snapshot và chứng từ nguồn.

Khi lọc khoảng ngày, số dư đầu kỳ phải bao gồm chứng từ trước ngày bắt đầu lọc; không được bỏ qua lịch sử chỉ vì chứng từ nằm ngoài khoảng xem.

## 5. Xuất báo cáo

Tất cả báo cáo dùng:

- **Xuất PDF** trực tiếp: thay thế hoàn toàn nút/API Word; tiêu đề, bảng, tổng cộng và chữ ký theo mẫu Word cũ ở mức gần nhất.
- **Xuất Excel**: vẫn giữ nguyên để phân tích số liệu.

Không còn quy trình hay lựa chọn xuất Word/DOCX. Nếu PDF hiển thị nhãn cột tiếng Anh, cần báo lỗi mapping PDF; nhãn nghiệp vụ phải là tiếng Việt.

## 6. Kịch bản thực hành

### A. Chuẩn bị danh mục

1. Kiểm tra khách hàng, chủ xe/hãng vận tải, xe và tài xế đang hoạt động.
2. Với chuyến xe đối tác, kiểm tra hãng vận tải, loại xe, biển số và tiền thuê; không tạo xe đối tác trong danh mục Xe.
3. Kiểm tra một chuyến hoàn tất có đủ cước, tiền thuê xe hoặc tiền thuê lái trước khi đối soát.

### B. Ghi nhận chứng từ

1. Vào **Tài chính và nhân sự** → **Thu chi**.
2. Tạo một Phiếu thu khách hàng, mở sửa và kiểm tra Ngày thu được điền lại đúng.
3. Tạo một Chi trả chủ xe và một Phiếu chi chung cho lái thuê.
4. Không nhập “công nợ” trực tiếp ở đây; mở Báo cáo để kiểm tra số dư thay đổi.

### C. Kiểm tra lợi nhuận và xuất file

1. Vào **Báo cáo** → **Lợi nhuận xe** và chọn khoảng ngày có chuyến hoàn tất.
2. Đối chiếu cước, tiền thuê xe, thuê lái và chứng từ chi với chuyến nguồn.
3. Xuất PDF, kiểm tra tiêu đề/cột tiếng Việt; sau đó xuất Excel để bảo đảm Excel không bị ảnh hưởng.

## 7. Checklist bàn giao

- Sidebar và tiêu đề trang hiển thị **Thu chi**, không còn “Thu chi và công nợ”.
- Finance không có tab Công nợ.
- Nhãn đúng là **Phiếu thu khách hàng** và **Chi trả chủ xe**.
- Sửa phiếu thu không mất Ngày thu.
- Công nợ chỉ xem trong Báo cáo và khớp ba công thức ở mục 4.
- PDF tiếng Việt, không dùng Word; Excel vẫn tải được.

## 8. Mốc source để debug

| Việc | Thành phần chính |
|---|---|
| Phiếu thu/phiếu chi/chi trả chủ xe | `app/Modules/Finance`, `src/modules/finance` |
| Công nợ | `app/Modules/Other/Services/DebtReportService.php` |
| Lợi nhuận và xuất PDF | `RestoredReportService`, `RestoredReportController`, `resources/views/reports/pdf.blade.php` |
| Danh mục xe | `app/Modules/MasterData`, `src/modules/master-data/vehicle` |
