# Tái cấu trúc xe chạy công nhân và xe chạy du lịch

## Mục tiêu

Thay thế toàn bộ luồng thuê xe, báo giá, hợp đồng, lịch chuyến, phân công và lệnh điều xe bằng hai màn hình vận hành trực tiếp:

- Xe chạy công nhân, được sinh tự động từ lịch lặp dài hạn.
- Xe chạy du lịch, được điều hành nhập và cập nhật trực tiếp.

Phạm vi áp dụng là database local/dev. Schema và dữ liệu nghiệp vụ cũ được thay thế hoàn toàn, không chuyển đổi dữ liệu cũ.

## Giai đoạn 1 - Chuẩn bị và thay schema nền tảng

### Mục tiêu

Xóa các luồng nghiệp vụ cũ và đưa database về cấu trúc phục vụ trực tiếp hai loại xe chạy.

### Công việc

- Bỏ `vehicles`, `vehicle_types`, `route_rates`, yêu cầu thuê xe, báo giá, hợp đồng, lịch chuyến, phân công và lệnh điều xe.
- Gỡ các routes, controllers, services, requests, models, frontend pages và permissions chỉ phục vụ những luồng trên.
- Tạo migration theo thứ tự phụ thuộc mới.
- Cập nhật `DatabaseSeeder` và `RefreshBusinessDataSeeder` theo schema mới.

### Bảng mới

#### `worker_vehicles`

Mỗi bản ghi là một lần xe chạy công nhân:

- `run_no`, `customer_id`, `route_id`, `shift_name`
- `run_date`, `return_date`, `pickup_time`, `return_time`
- `pickup_location`, `dropoff_location`
- `is_company_vehicle`, `partner_id`, `license_plate`
- `driver_id`, `is_hired_driver`, `hired_driver_name`
- `hired_driver_cost`, `partner_vehicle_cost`
- `freight_amount`, `driver_wage`
- `status`, `completed_at`, `note`

#### `tourist_vehicles`

Mỗi bản ghi là một lần xe chạy du lịch hoặc thuê cá nhân:

- Các trường chung tương ứng `worker_vehicles`.
- `journey`
- `driver_wage_rate`, `driver_wage`
- `driver_collected_amount`, `company_collected_amount`
- `driver_remitted_amount`, `driver_expense_amount`

Các khoản thu, nộp và chi của lái xe du lịch được nhập trực tiếp tại chuyến; không dùng bảng tiền lái xe nộp cũ.

#### `worker_vehicle_schedule_rules`

Quy tắc lịch chạy công nhân dài hạn:

- Tuyến, khách hàng, ca nối và ngày chạy trong tuần.
- Giờ/điểm đón trả.
- Biển số, lái xe, đối tác và trạng thái xe nhà.
- Ngày hiệu lực từ/đến.

#### `worker_vehicle_rates`

Bảng giá xe công nhân theo `route_id`, `license_plate` và thời gian hiệu lực:

- `freight_amount`
- `driver_wage`
- `effective_from`, `effective_to`

### Ràng buộc

- Không cho cùng biển số hoặc tài xế bị trùng khoảng thời gian giữa lịch công nhân và xe chạy du lịch.
- Không tạo trùng xe chạy công nhân cho cùng quy tắc lịch và ngày chạy.
- Cước và lương công nhân được chốt vào bản ghi xe chạy khi sinh lịch.

## Giai đoạn 2 - Xe chạy công nhân và lịch lặp

### Mục tiêu

Điều hành khai báo một lần lịch chạy công nhân dài hạn; hệ thống tự có dữ liệu xe chạy để cập nhật thực tế.

### Công việc

- Xây API, màn danh sách, chi tiết, tạo/sửa/xóa xe chạy công nhân.
- Xây màn cấu hình lịch lặp công nhân trong Quản lý xe chạy công nhân.
- Khi tạo hoặc cập nhật quy tắc, sinh idempotent các xe chạy trong 90 ngày tới.
- Thêm command chạy hằng ngày để luôn duy trì cửa sổ 90 ngày.
- Khi sinh xe chạy, tự lấy cước/lương theo tuyến, biển số và ngày hiệu lực của bảng giá.
- Điều hành cập nhật thông tin thực tế và xác nhận hoàn thành chuyến.

### Kết quả nghiệm thu

- Không cần nhập lặp chuyến công nhân hàng tuần.
- Lịch sinh đúng ngày, đúng ca, đúng xe, lái xe, cước và lương.
- Một thao tác hoàn thành chỉ sinh tối đa một chấm công.

## Giai đoạn 3 - Xe chạy du lịch và lương lái xe

### Mục tiêu

Cho phép điều hành tạo trực tiếp xe chạy du lịch mà không cần yêu cầu thuê, báo giá hay hợp đồng.

### Công việc

- Xây API, màn danh sách, chi tiết, tạo/sửa/xóa xe chạy du lịch.
- Điều hành chọn khách hàng, tài xế, loại xe nhà/xe đối tác hoặc thuê lái; nhập hành trình, biển số, thời gian và cước.
- Thêm `tourism_commission_rate` vào hồ sơ tài xế.
- Với xe nhà: sao chép tỷ lệ lương từ tài xế vào chuyến, tính lương = cước xe × tỷ lệ và cho phép điều hành chỉnh trước khi hoàn tất.
- Với xe đối tác: nhập tiền thuê xe đối tác.
- Với thuê lái: nhập tên lái thuê và tiền thuê lái.
- Nhập trực tiếp lái xe thu, công ty thu, lái xe nộp và lái xe chi tại chuyến du lịch.

### Kết quả nghiệm thu

- Không còn route, API hay menu của yêu cầu thuê xe, báo giá và hợp đồng.
- Mỗi chuyến chốt được cước, tỷ lệ lương và lương, không bị thay đổi khi hồ sơ tài xế được sửa sau này.

## Giai đoạn 4 - Chấm công, lương và tài chính

### Chấm công và lương

- Thay nguồn chấm công từ lệnh điều xe bằng `worker_vehicles` và `tourist_vehicles` hoàn thành.
- Kỳ lương tổng hợp công và lương của hai loại xe chạy.
- Chi tiết lương lưu nguồn xe chạy để có thể truy vết từng khoản công.

### Phiếu thu khách hàng

Giữ Phiếu thu khách hàng với các trường:

- Khách hàng, ngày thu, số tiền, phương thức thanh toán, diễn giải.

Bỏ liên kết hợp đồng, lệnh xe và nghiệp vụ khách trả trực tiếp qua lái xe.

### Chi trả đối tác

Giữ chi trả đối tác với các trường:

- Đối tác, ngày chi, số tiền, phương thức thanh toán, diễn giải.

Bỏ liên kết hợp đồng và lệnh xe.

### Phiếu chi chung

- Đổi tên Chi phí chung thành **Phiếu chi chung**.
- Chuyển sang nhập liệu thủ công theo ngày chứng từ để phục vụ báo cáo lợi nhuận.
- Giữ loại chi phí, ngày chi, số tiền, phương thức thanh toán, người nhận và diễn giải.
- Có loại chi phí **Chi trả thuê lái**, kèm tên lái thuê nhập tự do.
- Bỏ mục/bảng Tiền tài xế nộp.

### Công nợ

- Công nợ khách hàng = số dư đầu kỳ + cước xe công nhân + cước xe du lịch - tổng phiếu thu khách hàng.
- Công nợ đối tác = số dư đầu kỳ + tiền thuê xe công nhân + tiền thuê xe du lịch - tổng chi trả đối tác.
- Công nợ thuê lái = tổng tiền thuê lái ở hai bảng xe chạy - tổng Phiếu chi chung loại Chi trả thuê lái.
- Bỏ thẻ công nợ nhanh trên dashboard, nhưng giữ hai báo cáo công nợ khách hàng và đối tác.

## Giai đoạn 5 - Báo cáo và lợi nhuận

- Báo cáo xe chạy công nhân truy vấn trực tiếp `worker_vehicles`.
- Báo cáo xe chạy du lịch truy vấn trực tiếp `tourist_vehicles`.
- Báo cáo lợi nhuận tính doanh thu từ hai bảng xe chạy, sau đó trừ Phiếu chi chung và chi trả đối tác theo thời gian.
- Thêm phần Thuê lái trong Báo cáo lợi nhuận: phát sinh, đã trả và còn nợ.
- Cập nhật báo cáo bảng lương để hiển thị nguồn công từ hai loại xe chạy.

## Giai đoạn 6 - Quyền, sidebar và đổi mật khẩu

### Quyền

- Thêm `worker-vehicles.view` và `worker-vehicles.manage`.
- Thêm `tourist-vehicles.view` và `tourist-vehicles.manage`.
- Thêm quyền cấu hình lịch xe chạy công nhân.
- Cập nhật quyền mặc định cho quản trị, điều hành và kế toán.

### Sidebar

- Quản lý hệ thống: Quản lý người dùng, Quản lý vai trò, Quản lý phân quyền, Đổi mật khẩu.
- Quản lý danh mục: Khách hàng, Đối tác, Tài xế, Tuyến đường, Loại chi phí.
- Quản lý chạy xe: Quản lý xe chạy công nhân, Quản lý xe chạy du lịch.
- Giữ Tổng quan, Tài chính và nhân sự, Báo cáo.
- Bỏ nhóm Thuê xe, Điều hành và Không gian tài xế.

### Đổi mật khẩu

- Thêm API và trang đổi mật khẩu cho tài khoản đang đăng nhập.
- Bắt buộc nhập mật khẩu hiện tại, mật khẩu mới và xác nhận mật khẩu mới.

## Giai đoạn 7 - Seed, kiểm thử và nghiệm thu

- Viết lại seed theo schema mới, tối đa 5 bản ghi mỗi màn hình chính.
- Seed có lịch công nhân hiệu lực, xe chạy hoàn thành/chưa hoàn thành, chứng từ thu/chi, dữ liệu lương và báo cáo.
- Kiểm thử migration từ database trống; xác nhận schema và luồng cũ không còn.
- Kiểm thử sinh lịch 90 ngày, chống trùng, chốt cước/lương và kiểm tra xung đột xe/tài xế.
- Kiểm thử lương xe nhà, xe đối tác, thuê lái, chấm công tự động và kỳ lương.
- Kiểm thử công thức công nợ khách hàng, đối tác, thuê lái và báo cáo lợi nhuận.
- Chạy PHPUnit, Pint, frontend unit tests, type-check và build.

## Lệnh áp dụng local/dev

Sau khi hoàn tất migration mới, dùng database sạch và seed lại:

```powershell
php artisan migrate:fresh
php artisan db:seed
```
