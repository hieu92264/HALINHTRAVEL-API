# Sổ tay tự học điều hành xe chạy và lương lái xe — Nguyễn Mạnh Trường

## 0. Mục tiêu và phạm vi

Trường cần có thể tạo, hoàn tất và đối soát một chuyến xe công nhân hoặc xe du lịch; sau đó lần số liệu tới chấm công và kỳ lương.

Tài liệu áp dụng nghiệp vụ đã chốt tháng 11/2026: **Loại xe / xe đối tác theo từng chuyến / giá tuyến × loại xe** là quy tắc đang dùng. Backend là nơi duy nhất tính và snapshot cước/lương.

## 1. Quy tắc nguồn xe

Nguồn xe là một lựa chọn nghiệp vụ, không phải checkbox để người dùng tự đoán:

| Nguồn | Cách chọn | Dữ liệu bắt buộc | Ý nghĩa tài chính |
|---|---|---|---|
| Xe công ty | Chọn xe công ty | Biển số; tài xế công ty hoặc thuê lái | Không có tiền thuê xe đối tác |
| Xe đối tác | Chọn chủ xe/hãng vận tải rồi nhập biển số tay | Chủ xe/hãng vận tải, loại xe, biển số, tiền thuê xe | Phát sinh công nợ chủ xe khi chuyến hoàn tất |
| Thuê lái | Là trạng thái tài xế của từng chuyến, độc lập nguồn xe | Tên lái thuê, tiền thuê lái | Phát sinh công nợ thuê lái khi cờ thuê lái bật |

Danh mục **Xe** chỉ chứa xe công ty, mỗi xe có Loại xe và tài xế mặc định. Xe đối tác không được thêm vào danh mục; biển số chỉ là snapshot của từng chuyến. Tài xế ngoài cũng không được tạo danh mục, mà nhập dưới dạng thuê lái của chuyến.

## 2. Tuyến, loại xe và giá

Một tuyến có mã, tên, ca, khách hàng, điểm/giờ đón và trả. Quy tắc nghiệp vụ mới là mỗi ô **tuyến × loại xe** có một cước xe và một lương lái công nhân cố định.

Khi chọn tuyến và loại xe:

1. Hệ thống lấy đúng cước và lương từ bảng giá.
2. Hai giá trị được snapshot vào chuyến.
3. Sửa bảng giá sau này không làm đổi chuyến hoặc kỳ lương đã có.

Một tuyến chỉ được dùng tạo chuyến công nhân khi đủ sáu dòng giá `Xe04`, `Xe07`, `Xe16`, `Xe29`, `Xe34`, `Xe45`; cước và lương lái từng dòng đều lớn hơn 0.

## 3. Xe chạy công nhân

Mỗi chuyến công nhân cần có khách hàng, tuyến, ngày chạy, ca, giờ/điểm đón trả, nguồn xe và trạng thái.

- Chọn **Tuyến trước**. Khách hàng, ca, giờ/điểm đón trả, cước và lương là dữ liệu snapshot chỉ đọc.
- Xe công ty tự điền biển số, loại xe, số chỗ và tài xế mặc định; điều độ có thể đổi tài xế hoặc bật thuê lái.
- Xe đối tác bắt buộc chủ xe/hãng vận tải, loại xe, biển số tay và tiền thuê xe.
- Chỉ chuyến `completed` mới đi vào công nợ, chấm công và báo cáo có điều kiện hoàn tất.
- Giờ thực tế `actual_started_at`/`actual_completed_at` là căn cứ cho dữ liệu hoàn tất; dữ liệu cũ có thể dùng `completed_at` khi chưa có giờ thực tế.

## 4. Xe chạy du lịch và bốn khoản đối soát

Xe du lịch nhập tay khách hàng, hành trình, lịch trình và cước xe. Lương lái du lịch được tính:

`lương lái du lịch = cước xe × % lương lái du lịch của loại xe`

Tỷ lệ và số tiền phải được snapshot vào chuyến. Tỷ lệ lấy từ **Loại xe**, không lấy từ hồ sơ tài xế và không được nhập tay.

Trên chuyến du lịch phải phân biệt bốn khoản, không gộp chúng với nhau:

| Khoản | Tác động tới lương lái | Tác động khác |
|---|---:|---|
| Lái xe thu khách | Trừ thực lĩnh | Không phải tiền công ty đã thu |
| Công ty thu | Không tác động | Theo dõi dòng tiền công ty |
| Lái xe nộp công ty | Cộng thực lĩnh | Xác nhận lái đã nộp lại |
| Lái xe tự chi | Cộng thực lĩnh | Hoàn/ghi nhận khoản lái tự chi |

## 5. Từ chuyến hoàn tất đến chấm công

Với xe công ty do tài xế công ty chạy, hệ thống tạo chấm công từ chuyến hoàn tất:

- Chuyến công nhân: tiền lương snapshot của chuyến.
- Chuyến du lịch: lương snapshot của chuyến, không tính lại theo bảng giá đang hiện hành.
- Chuyến dùng xe đối tác hoặc thuê lái không tạo lương cho tài xế công ty chỉ vì có biển số/chủ xe.

Chấm công cần ở trạng thái `confirmed` trước khi tính vào kỳ lương. Khi kỳ lương đã khóa, chấm công liên quan chuyển `payroll_locked` và không được sửa.

## 6. Kỳ lương nhiều bản

Kỳ lương là snapshot toàn bộ dữ liệu hợp lệ của một tháng, không phải một màn hình tính lại động.

1. Có thể tạo kỳ cho tháng hiện tại hoặc tháng tương lai nếu đã có chuyến hoàn tất thuộc tháng đó; không cần chờ hết tháng.
2. Bản đầu là `Tháng MM/YYYY - Bản 1`; tạo lại sẽ là Bản 2, Bản 3…
3. Khi chưa có bản nào `paid` hoặc `locked`, tạo bản mới sẽ đặt bản mới là hiện hành (`is_current = true`) và chuyển bản cũ thành `superseded`.
4. Khi một bản trong tháng đã trả hoặc khóa, không được tạo bản mới.
5. Báo cáo mặc định đọc bản hiện hành; bản thay thế chỉ để đối chiếu, không dùng thanh toán.

Vòng đời: `draft → calculated → approved → paid → locked`; trạng thái `superseded` là bản đã bị thay thế.

### Công thức thực lĩnh

`thực lĩnh = trách nhiệm + lương công nhân + lương du lịch - tạm ứng - lái xe thu khách + lái xe nộp công ty + lái xe tự chi`

Khoản **công ty thu** không nằm trong công thức. Các khoản phụ cấp/khấu trừ khác chỉ được thêm khi có quyết định rõ ràng và phải hiển thị tách riêng trong chi tiết.

Ví dụ: trách nhiệm 1.000.000, công nhân 300.000, du lịch 600.000, tạm ứng 200.000, lái thu 450.000, lái nộp 200.000, lái tự chi 50.000 thì thực lĩnh là **1.500.000 đồng**.

## 7. Quy trình thao tác kiểm tra

1. Kiểm tra khách hàng, chủ xe/hãng vận tải, xe và tài xế trước khi lập chuyến.
2. Tạo chuyến, kiểm tra nguồn xe, tài xế/thuê lái, cước, lương và các snapshot.
3. Hoàn tất chuyến với ngày giờ thực tế.
4. Vào **Chấm công và lương** → xác nhận chấm công.
5. Tạo kỳ lương đúng tháng, bấm tính lương, mở chi tiết để kiểm tra bốn khoản đối soát du lịch.
6. Nếu phải tính lại trước trả/khóa, tạo bản mới thay vì sửa số liệu của bản cũ; kiểm tra bản mới là hiện hành.

## 8. Các kiểm tra bắt buộc

- Không tạo được chuyến có nguồn xe mâu thuẫn với xe/chủ xe đã chọn.
- Sửa giá sau khi có chuyến không làm đổi cước/lương snapshot của chuyến cũ.
- Xe đối tác hoàn tất có tiền thuê xe; thuê lái có tên và chi phí thuê lái.
- Lái thu, lái nộp và lái tự chi xuất hiện riêng trong bảng lương; công ty thu không làm đổi thực lĩnh.
- Không tạo được Bản 2 sau khi một bản của tháng đã `paid` hoặc `locked`.
- Báo cáo lương mặc định lấy bản `is_current = true`.

## 9. Mốc source để lần lỗi

| Việc | Thành phần chính |
|---|---|
| Chuyến công nhân/du lịch | `app/Modules/VehicleOperations`, `src/modules/vehicle-operations` |
| Snapshot giá/lương | `RouteVehicleTypeRate`, `WorkerVehicle`, `TouristVehicle` |
| Chấm công và kỳ lương | `DriverPayrollService`, `Payroll`, `PayrollItem` |
| Bản kỳ lương | migration `2026_11_04_000000_add_revisions_and_settlements_to_payrolls.php` |

Khi debug, kiểm tra Network theo thứ tự: payload chuyến → bản ghi chuyến snapshot → attendance → payroll item. Không sửa trực tiếp số liệu trong bảng lương để che lỗi ở chuyến nguồn.
