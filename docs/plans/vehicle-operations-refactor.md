# Seed lại dữ liệu mẫu có `vehicle_id`

## Tóm tắt

Tạo quy trình reseed riêng cho môi trường local: xóa toàn bộ dữ liệu nghiệp vụ mẫu/phụ thuộc, giữ danh mục và tài khoản, sau đó tạo lại dữ liệu xe chạy, lịch lặp và bảng giá với `vehicle_id` đầy đủ. Không chạy migration.

## Thay đổi chính

- Giữ `RefreshBusinessDataSeeder` cho seed trên database trống; cập nhật toàn bộ dữ liệu mẫu để:
    - Bảng giá công nhân có `vehicle_id` và biển số snapshot khớp xe.
    - Lịch lặp có `vehicle_id`, nguồn xe, đối tác và biển số snapshot khớp xe.
    - Xe chạy công nhân/du lịch có `vehicle_id` hợp lệ; xe đối tác có `partner_id` khớp với chủ xe trong danh mục.
    - Chuyến sinh từ lịch lặp chỉ dùng đúng xe của lịch; các chuyến xe khác để là chuyến tạo tay (`schedule_rule_id = null`) thay vì liên kết nhầm lịch.

- Thêm seeder reseed chủ động, chỉ dùng khi cần làm sạch dữ liệu local:
    - Xóa theo thứ tự an toàn các dữ liệu nghiệp vụ và bản ghi phụ thuộc: chi tiết lương/chấm công, lương/tạm ứng, thu chi/thanh toán đối tác, xe chạy, ngày lịch lặp, lịch lặp và bảng giá.
    - Không xóa danh mục xe, khách hàng, đối tác, tài xế, tuyến đường hoặc tài khoản.
    - Sau khi xóa, gọi seed danh mục xe và seed dữ liệu nghiệp vụ mới.

- Không thay đổi dữ liệu schema hay migration; các cột snapshot như `license_plate`, `partner_id`, `is_company_vehicle` vẫn được seed đồng bộ từ Vehicle.

## Kiểm thử

- Seed database trống: mọi bảng giá, lịch lặp, xe công nhân và xe du lịch mẫu đều có `vehicle_id` khác null.
- Kiểm tra `vehicle_id` khớp biển số/chủ xe/nguồn xe snapshot.
- Chạy reseed hai lần: không tạo bản ghi trùng, số lượng dữ liệu mẫu ổn định.
- Kiểm tra dữ liệu danh mục và người dùng vẫn còn sau reseed.
- Kiểm tra cập nhật lịch lặp và chuyến xe mẫu không còn lỗi “không có bảng giá hiệu lực” do bảng giá cũ thiếu `vehicle_id`.

## Cách áp dụng sau khi triển khai

Chạy seeder reseed chuyên dụng trên database local để thay thế dữ liệu nghiệp vụ hiện tại bằng dữ liệu mẫu mới có liên kết xe đầy đủ. Dữ liệu nghiệp vụ hiện tại sẽ bị xóa theo lựa chọn đã chốt; danh mục và tài khoản được giữ lại.
