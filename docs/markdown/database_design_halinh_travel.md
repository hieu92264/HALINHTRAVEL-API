# DATABASE DESIGN – HÀ LINH TRAVEL

> Thiết kế đề xuất cho hệ thống quản lý dịch vụ cho thuê xe của Công ty TNHH Vận tải & Du lịch Hà Linh.
>
> Giả định stack: Laravel + MySQL + Spatie Laravel Permission.
>
> Quy ước:
> - Primary key: `BIGINT UNSIGNED`
> - Tiền: `DECIMAL(18,2)`
> - Boolean: `BOOLEAN`
> - Các trạng thái nghiệp vụ nên lưu `VARCHAR` và quản lý bằng PHP Enum thay vì MySQL ENUM để dễ thay đổi.
> - Các bảng nghiệp vụ dùng `created_at`, `updated_at`; khi cần audit thêm `created_by`, `updated_by` FK -> `users.id`.
> - Không lưu `user_name_created` / `user_name_updated` nếu có thể dùng FK đến `users`.

---

# 1. ROLES ĐỀ XUẤT

| Role | Ý nghĩa | Phạm vi chính |
|---|---|---|
| `admin` | Quản trị hệ thống | User, role, permission, cấu hình, toàn bộ hệ thống |
| `director` | Ban giám đốc | Xem toàn hệ thống, báo cáo, công nợ, lợi nhuận, phê duyệt |
| `sales` | Nhân viên kinh doanh | Khách hàng, yêu cầu thuê xe, báo giá, hợp đồng |
| `dispatcher` | Nhân viên điều hành | Xe, tài xế, lịch chạy, phân xe, phân tài xế, lệnh điều xe |
| `accountant` | Kế toán | Thu tiền, chi phí, trả đối tác, tạm ứng, chấm công, lương, công nợ |
| `driver` | Tài xế | Xem chuyến được giao, cập nhật chuyến, km thực tế, chi phí/chứng từ |

> Tài xế thuê ngoài không bắt buộc phải có tài khoản. `drivers.user_id` có thể `NULL`.

---

# 2. NHÓM AUTHENTICATION & AUTHORIZATION

## users

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | 1 |
| username | VARCHAR(100) UNIQUE | admin |
| name | VARCHAR(150) | Nguyễn Văn A |
| email | VARCHAR(255) UNIQUE NULL | a@example.com |
| phone | VARCHAR(20) NULL | 0900000000 |
| password | VARCHAR(255) | Laravel hashed password |
| last_login_at | DATETIME NULL | |
| email_verified_at | TIMESTAMP NULL | |
| remember_token | VARCHAR(100) NULL | |
| is_active | BOOLEAN DEFAULT 1 | |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

### Spatie Permission

Giữ đúng migration do Spatie sinh ra, không cần thêm nghiệp vụ vào các bảng này:

- `roles`
- `permissions`
- `model_has_roles`
- `model_has_permissions`
- `role_has_permissions`

## roles

| Variable | Type | Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| name | VARCHAR(255) | admin, dispatcher... |
| guard_name | VARCHAR(255) | web |

## permissions

| Variable | Type | Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| name | VARCHAR(255) | vehicle.view |
| guard_name | VARCHAR(255) | web |

## model_has_roles

| Variable | Type | Note |
|---|---|---|
| role_id | FK -> roles.id | |
| model_type | VARCHAR(255) | App\Models\User |
| model_id | BIGINT UNSIGNED | users.id |

## model_has_permissions

| Variable | Type | Note |
|---|---|---|
| permission_id | FK -> permissions.id | |
| model_type | VARCHAR(255) | App\Models\User |
| model_id | BIGINT UNSIGNED | users.id |

## role_has_permissions

| Variable | Type | Note |
|---|---|---|
| permission_id | FK -> permissions.id | |
| role_id | FK -> roles.id | |

---

# 3. NHÓM DANH MỤC

## customers – Khách hàng

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| code | VARCHAR(50) UNIQUE | KH0001 |
| type | VARCHAR(30) | individual / company |
| name | VARCHAR(255) | |
| phone | VARCHAR(20) NULL | |
| email | VARCHAR(255) NULL | |
| cccd | VARCHAR(20) NULL | Cá nhân |
| tax_code | VARCHAR(30) NULL | Doanh nghiệp |
| address | VARCHAR(500) NULL | |
| contact_name | VARCHAR(255) NULL | Người liên hệ |
| opening_balance | DECIMAL(18,2) DEFAULT 0 | Công nợ đầu kỳ |
| is_active | BOOLEAN DEFAULT 1 | |
| created_by | BIGINT UNSIGNED FK NULL | users.id |
| updated_by | BIGINT UNSIGNED FK NULL | users.id |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

## partners – Đối tác / chủ xe / nhà cung cấp

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| code | VARCHAR(50) UNIQUE | DT0001 |
| type | VARCHAR(50) | transport_company / vehicle_owner / garage / fuel_supplier / other |
| name | VARCHAR(255) | |
| phone | VARCHAR(20) NULL | |
| email | VARCHAR(255) NULL | |
| cccd | VARCHAR(20) NULL | |
| tax_code | VARCHAR(30) NULL | |
| address | VARCHAR(500) NULL | |
| bank_name | VARCHAR(255) NULL | |
| bank_account | VARCHAR(100) NULL | |
| opening_balance | DECIMAL(18,2) DEFAULT 0 | Công nợ phải trả đầu kỳ |
| is_active | BOOLEAN DEFAULT 1 | |
| created_by | BIGINT UNSIGNED FK NULL | users.id |
| updated_by | BIGINT UNSIGNED FK NULL | users.id |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

## vehicle_types – Loại xe

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| code | VARCHAR(30) UNIQUE | XE16 |
| name | VARCHAR(100) | Xe 16 chỗ |
| seats | INT | 16 |
| tour_driver_commission_rate | DECIMAL(5,2) DEFAULT 0 | % lương tài xế chuyến du lịch |
| is_active | BOOLEAN DEFAULT 1 | |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

## vehicles – Xe

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| license_plate | VARCHAR(20) UNIQUE | 15B-123.45 |
| vehicle_type_id | BIGINT UNSIGNED FK | vehicle_types.id |
| ownership_type | VARCHAR(30) | company / partner |
| partner_id | BIGINT UNSIGNED FK NULL | Bắt buộc khi ownership_type=partner |
| brand | VARCHAR(100) NULL | Ford |
| model | VARCHAR(100) NULL | Transit |
| manufacture_year | SMALLINT NULL | 2024 |
| current_odometer | INT UNSIGNED NULL | km |
| operational_status | VARCHAR(30) | available / assigned / maintenance / inactive |
| notes | TEXT NULL | |
| is_active | BOOLEAN DEFAULT 1 | |
| created_by | BIGINT UNSIGNED FK NULL | users.id |
| updated_by | BIGINT UNSIGNED FK NULL | users.id |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

## drivers – Tài xế

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| code | VARCHAR(50) UNIQUE | LX0001 |
| user_id | BIGINT UNSIGNED FK UNIQUE NULL | users.id |
| partner_id | BIGINT UNSIGNED FK NULL | Đối tác nếu tài xế ngoài |
| type | VARCHAR(30) | company / external |
| full_name | VARCHAR(255) | |
| phone | VARCHAR(20) NULL | |
| cccd | VARCHAR(20) UNIQUE NULL | |
| license_number | VARCHAR(50) UNIQUE | |
| license_class | VARCHAR(20) | D |
| license_issued_at | DATE NULL | |
| license_expired_at | DATE NULL | |
| base_salary | DECIMAL(18,2) DEFAULT 0 | Nếu áp dụng |
| responsibility_allowance | DECIMAL(18,2) DEFAULT 0 | Phụ cấp trách nhiệm |
| joined_at | DATE NULL | |
| left_at | DATE NULL | |
| is_active | BOOLEAN DEFAULT 1 | |
| created_by | BIGINT UNSIGNED FK NULL | users.id |
| updated_by | BIGINT UNSIGNED FK NULL | users.id |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

## routes – Tuyến đường

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| code | VARCHAR(50) UNIQUE | TUYEN001 |
| customer_id | BIGINT UNSIGNED FK NULL | Tuyến riêng của khách nếu có |
| name | VARCHAR(255) | VSIP - Thủy Nguyên |
| shift_name | VARCHAR(100) NULL | Ca sáng |
| pickup_location | VARCHAR(500) | |
| dropoff_location | VARCHAR(500) | |
| default_pickup_time | TIME NULL | |
| default_return_time | TIME NULL | |
| estimated_distance_km | DECIMAL(10,2) NULL | |
| is_active | BOOLEAN DEFAULT 1 | |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

## route_rates – Bảng giá tuyến

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| route_id | BIGINT UNSIGNED FK | routes.id |
| vehicle_type_id | BIGINT UNSIGNED FK | vehicle_types.id |
| customer_price | DECIMAL(18,2) | Cước thu khách |
| driver_wage | DECIMAL(18,2) DEFAULT 0 | Lương/chuyến tuyến cố định |
| effective_from | DATE | |
| effective_to | DATE NULL | |
| is_active | BOOLEAN DEFAULT 1 | |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

Unique đề xuất: `(route_id, vehicle_type_id, effective_from)`.

## expense_types – Loại chi phí

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| code | VARCHAR(50) UNIQUE | FUEL |
| name | VARCHAR(150) | Xăng dầu |
| scope | VARCHAR(30) | vehicle / trip / general |
| is_active | BOOLEAN DEFAULT 1 | |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

---

# 4. YÊU CẦU THUÊ XE – BÁO GIÁ

## rental_requests – Yêu cầu thuê xe

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| request_no | VARCHAR(50) UNIQUE | YC20260001 |
| customer_id | BIGINT UNSIGNED FK | customers.id |
| source | VARCHAR(30) NULL | phone / zalo / facebook / direct |
| requested_at | DATETIME | |
| service_type | VARCHAR(30) | fixed / tourism / school / business |
| pickup_location | VARCHAR(500) NULL | |
| dropoff_location | VARCHAR(500) NULL | |
| start_at | DATETIME NULL | |
| end_at | DATETIME NULL | |
| note | TEXT NULL | |
| status | VARCHAR(30) | new / quoted / accepted / rejected / converted |
| created_by | BIGINT UNSIGNED FK NULL | users.id |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

## rental_request_items – Chi tiết yêu cầu

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| rental_request_id | BIGINT UNSIGNED FK | rental_requests.id |
| vehicle_type_id | BIGINT UNSIGNED FK | vehicle_types.id |
| quantity | INT UNSIGNED DEFAULT 1 | |
| route_id | BIGINT UNSIGNED FK NULL | |
| note | TEXT NULL | |

## quotations – Báo giá

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| quotation_no | VARCHAR(50) UNIQUE | BG20260001 |
| rental_request_id | BIGINT UNSIGNED FK NULL | |
| customer_id | BIGINT UNSIGNED FK | |
| quotation_date | DATE | |
| valid_until | DATE NULL | |
| subtotal | DECIMAL(18,2) DEFAULT 0 | |
| discount_amount | DECIMAL(18,2) DEFAULT 0 | |
| total_amount | DECIMAL(18,2) DEFAULT 0 | |
| payment_terms | TEXT NULL | |
| status | VARCHAR(30) | draft / sent / approved / rejected / expired |
| approved_by | BIGINT UNSIGNED FK NULL | users.id |
| approved_at | DATETIME NULL | |
| created_by | BIGINT UNSIGNED FK NULL | users.id |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

## quotation_items – Chi tiết báo giá

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| quotation_id | BIGINT UNSIGNED FK | quotations.id |
| route_id | BIGINT UNSIGNED FK NULL | |
| vehicle_type_id | BIGINT UNSIGNED FK | |
| description | VARCHAR(500) NULL | |
| quantity | INT UNSIGNED DEFAULT 1 | |
| unit_price | DECIMAL(18,2) | |
| amount | DECIMAL(18,2) | |

---

# 5. HỢP ĐỒNG

## contracts – Hợp đồng

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| contract_no | VARCHAR(50) UNIQUE | HD20260001 |
| customer_id | BIGINT UNSIGNED FK | customers.id |
| rental_request_id | BIGINT UNSIGNED FK NULL | |
| quotation_id | BIGINT UNSIGNED FK NULL | |
| contract_type | VARCHAR(30) | trip / principle |
| signed_date | DATE NULL | |
| effective_from | DATE | |
| effective_to | DATE NULL | Hợp đồng chuyến có thể cùng ngày |
| total_amount | DECIMAL(18,2) DEFAULT 0 | |
| deposit_required | DECIMAL(18,2) DEFAULT 0 | |
| payment_terms | TEXT NULL | |
| terms | LONGTEXT NULL | Điều khoản |
| status | VARCHAR(30) | draft / active / completed / cancelled |
| created_by | BIGINT UNSIGNED FK NULL | users.id |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

## contract_items – Dịch vụ trong hợp đồng

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| contract_id | BIGINT UNSIGNED FK | contracts.id |
| service_type | VARCHAR(30) | fixed / tourism / school / business |
| route_id | BIGINT UNSIGNED FK NULL | |
| vehicle_type_id | BIGINT UNSIGNED FK | |
| quantity | INT UNSIGNED DEFAULT 1 | |
| unit_price | DECIMAL(18,2) | |
| driver_wage | DECIMAL(18,2) DEFAULT 0 | |
| pickup_location | VARCHAR(500) NULL | |
| dropoff_location | VARCHAR(500) NULL | |
| note | TEXT NULL | |

---

# 6. LỊCH CỐ ĐỊNH & SINH CHUYẾN

Hợp đồng công nhân/học sinh kéo dài nhiều tháng nên không nên nhập từng ngày trực tiếp vào hợp đồng. Lưu "quy tắc chạy", sau đó sinh các `trip_schedules` theo ngày.

## contract_schedule_rules – Quy tắc lịch cố định

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| contract_item_id | BIGINT UNSIGNED FK | contract_items.id |
| route_id | BIGINT UNSIGNED FK NULL | |
| effective_from | DATE | |
| effective_to | DATE | |
| default_vehicle_id | BIGINT UNSIGNED FK NULL | Xe cứng |
| default_driver_id | BIGINT UNSIGNED FK NULL | Tài xế cứng |
| is_active | BOOLEAN DEFAULT 1 | |
| note | TEXT NULL | |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

## contract_schedule_days – Ngày/ca chạy định kỳ

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| schedule_rule_id | BIGINT UNSIGNED FK | contract_schedule_rules.id |
| weekday | TINYINT | 1=Monday ... 7=Sunday |
| pickup_time | TIME | |
| return_time | TIME NULL | |
| shift_name | VARCHAR(100) NULL | Ca sáng |

Unique đề xuất: `(schedule_rule_id, weekday, pickup_time)`.

---

# 7. LỊCH TRÌNH & ĐIỀU XE

## trip_schedules – Lịch chuyến thực tế

Đây là bảng trung tâm để kiểm tra xe/tài xế có bị trùng lịch hay không.

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| schedule_no | VARCHAR(50) UNIQUE | LT20260925001 |
| contract_id | BIGINT UNSIGNED FK | |
| contract_item_id | BIGINT UNSIGNED FK NULL | |
| schedule_rule_id | BIGINT UNSIGNED FK NULL | Lịch cố định sinh từ rule |
| service_type | VARCHAR(30) | fixed / tourism / school / business |
| route_id | BIGINT UNSIGNED FK NULL | |
| scheduled_start_at | DATETIME | |
| scheduled_end_at | DATETIME | |
| pickup_location | VARCHAR(500) NULL | |
| dropoff_location | VARCHAR(500) NULL | |
| journey | TEXT NULL | Hành trình du lịch |
| required_vehicle_type_id | BIGINT UNSIGNED FK NULL | |
| status | VARCHAR(30) | planned / assigned / in_progress / completed / cancelled |
| note | TEXT NULL | |
| created_by | BIGINT UNSIGNED FK NULL | users.id |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

Index quan trọng:
- `(scheduled_start_at, scheduled_end_at, status)`
- `contract_id`
- `route_id`

## trip_assignments – Phân xe & tài xế

Cho phép lưu lịch sử thay xe/tài xế khi xe hỏng hoặc tài xế nghỉ.

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| trip_schedule_id | BIGINT UNSIGNED FK | trip_schedules.id |
| vehicle_id | BIGINT UNSIGNED FK | vehicles.id |
| driver_id | BIGINT UNSIGNED FK | drivers.id |
| partner_id | BIGINT UNSIGNED FK NULL | Nếu thuê xe ngoài |
| assignment_type | VARCHAR(30) | primary / substitute |
| replaced_assignment_id | BIGINT UNSIGNED FK NULL | Chính assignment bị thay |
| replace_reason | VARCHAR(500) NULL | Xe hỏng / tài xế nghỉ |
| assigned_at | DATETIME | |
| assigned_by | BIGINT UNSIGNED FK | users.id |
| is_current | BOOLEAN DEFAULT 1 | |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

Index bắt buộc:
- `(vehicle_id, is_current)`
- `(driver_id, is_current)`
- `trip_schedule_id`

> Chống trùng lịch phải kiểm tra bằng service/transaction trước khi insert/update assignment:
>
> `existing.start < new_end AND existing.end > new_start`

## dispatch_orders – Lệnh điều xe

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| order_no | VARCHAR(50) UNIQUE | LDX20260001 |
| trip_schedule_id | BIGINT UNSIGNED FK UNIQUE | |
| trip_assignment_id | BIGINT UNSIGNED FK | Assignment hiện hành |
| issued_at | DATETIME | |
| issued_by | BIGINT UNSIGNED FK | users.id |
| actual_start_at | DATETIME NULL | |
| actual_end_at | DATETIME NULL | |
| start_odometer | INT UNSIGNED NULL | |
| end_odometer | INT UNSIGNED NULL | |
| actual_distance_km | DECIMAL(10,2) NULL | |
| waiting_hours | DECIMAL(6,2) DEFAULT 0 | |
| customer_amount | DECIMAL(18,2) DEFAULT 0 | Doanh thu chuyến |
| partner_vehicle_cost | DECIMAL(18,2) DEFAULT 0 | Nếu xe ngoài |
| external_driver_cost | DECIMAL(18,2) DEFAULT 0 | Nếu thuê lái |
| status | VARCHAR(30) | issued / accepted / in_progress / completed / cancelled |
| completed_at | DATETIME NULL | |
| note | TEXT NULL | |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

---

# 8. CHẤM CÔNG TÀI XẾ

## driver_attendances – Chấm công theo chuyến

Sinh khi `dispatch_orders.status = completed`.

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| driver_id | BIGINT UNSIGNED FK | drivers.id |
| dispatch_order_id | BIGINT UNSIGNED FK UNIQUE | |
| work_date | DATE | |
| work_type | VARCHAR(30) | fixed_trip / tourism_trip / other |
| work_units | DECIMAL(8,2) DEFAULT 1 | |
| base_amount | DECIMAL(18,2) DEFAULT 0 | Giá trị dùng tính lương |
| rate | DECIMAL(8,2) DEFAULT 0 | Đơn giá hoặc % |
| calculated_wage | DECIMAL(18,2) DEFAULT 0 | |
| status | VARCHAR(30) | pending / confirmed / payroll_locked |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

---

# 9. THU – CHI

## receipts – Phiếu thu khách hàng

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| receipt_no | VARCHAR(50) UNIQUE | PT20260001 |
| customer_id | BIGINT UNSIGNED FK | |
| contract_id | BIGINT UNSIGNED FK NULL | |
| receipt_type | VARCHAR(30) | deposit / contract_payment / other |
| received_at | DATETIME | |
| amount | DECIMAL(18,2) | |
| payment_method | VARCHAR(30) | cash / bank_transfer |
| payer_name | VARCHAR(255) NULL | |
| description | VARCHAR(500) NULL | |
| is_locked | BOOLEAN DEFAULT 0 | Khóa sổ |
| created_by | BIGINT UNSIGNED FK NULL | users.id |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

Công nợ khách hàng có thể tính:
`opening_balance + giá trị phải thu - tổng receipts`.

## expenses – Chi phí xe / chuyến / chi phí chung

Gộp bảng "chi phí xe" và "chi phí chung" bằng cột `scope`, tránh lặp cấu trúc.

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| expense_no | VARCHAR(50) UNIQUE | PC20260001 |
| expense_type_id | BIGINT UNSIGNED FK | expense_types.id |
| scope | VARCHAR(30) | vehicle / trip / general |
| vehicle_id | BIGINT UNSIGNED FK NULL | |
| dispatch_order_id | BIGINT UNSIGNED FK NULL | |
| partner_id | BIGINT UNSIGNED FK NULL | Gara / cây xăng... |
| driver_id | BIGINT UNSIGNED FK NULL | Người chi / ứng chi |
| expense_date | DATETIME | |
| amount | DECIMAL(18,2) | |
| payment_method | VARCHAR(30) NULL | |
| document_no | VARCHAR(100) NULL | Số hóa đơn/chứng từ |
| description | VARCHAR(500) NULL | |
| is_locked | BOOLEAN DEFAULT 0 | |
| created_by | BIGINT UNSIGNED FK NULL | |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

## partner_payments – Chi trả chủ xe / đối tác

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| payment_no | VARCHAR(50) UNIQUE | CTDT20260001 |
| partner_id | BIGINT UNSIGNED FK | |
| dispatch_order_id | BIGINT UNSIGNED FK NULL | Có thể thanh toán theo chuyến |
| paid_at | DATETIME | |
| amount | DECIMAL(18,2) | |
| payment_method | VARCHAR(30) | cash / bank_transfer |
| description | VARCHAR(500) NULL | |
| is_locked | BOOLEAN DEFAULT 0 | |
| created_by | BIGINT UNSIGNED FK NULL | |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

Công nợ đối tác:
`opening_balance + chi phí thuê xe/đối tác phải trả - partner_payments`.

## driver_advances – Tạm ứng lương tài xế

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| advance_no | VARCHAR(50) UNIQUE | TU20260001 |
| driver_id | BIGINT UNSIGNED FK | |
| advance_date | DATE | |
| amount | DECIMAL(18,2) | |
| description | VARCHAR(500) NULL | |
| status | VARCHAR(30) | pending / approved / paid / cancelled |
| approved_by | BIGINT UNSIGNED FK NULL | |
| created_by | BIGINT UNSIGNED FK NULL | |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

---

# 10. BẢNG LƯƠNG

## payrolls – Kỳ lương

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| code | VARCHAR(50) UNIQUE | LUONG-2026-09 |
| month | TINYINT | 9 |
| year | SMALLINT | 2026 |
| from_date | DATE | |
| to_date | DATE | |
| status | VARCHAR(30) | draft / calculated / approved / paid / locked |
| approved_by | BIGINT UNSIGNED FK NULL | |
| approved_at | DATETIME NULL | |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

Unique: `(month, year)`.

## payroll_items – Lương từng tài xế

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| payroll_id | BIGINT UNSIGNED FK | |
| driver_id | BIGINT UNSIGNED FK | |
| base_salary | DECIMAL(18,2) DEFAULT 0 | |
| responsibility_allowance | DECIMAL(18,2) DEFAULT 0 | |
| meal_allowance | DECIMAL(18,2) DEFAULT 0 | |
| fixed_trip_wage | DECIMAL(18,2) DEFAULT 0 | |
| tourism_commission | DECIMAL(18,2) DEFAULT 0 | |
| other_allowance | DECIMAL(18,2) DEFAULT 0 | |
| advance_amount | DECIMAL(18,2) DEFAULT 0 | Tổng tạm ứng |
| deduction_amount | DECIMAL(18,2) DEFAULT 0 | |
| gross_salary | DECIMAL(18,2) DEFAULT 0 | |
| net_salary | DECIMAL(18,2) DEFAULT 0 | |
| note | TEXT NULL | |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

Unique: `(payroll_id, driver_id)`.

## payroll_item_details – Chi tiết nguồn tính lương

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| payroll_item_id | BIGINT UNSIGNED FK | |
| driver_attendance_id | BIGINT UNSIGNED FK NULL | |
| dispatch_order_id | BIGINT UNSIGNED FK NULL | |
| calculation_type | VARCHAR(30) | fixed_trip / tourism_commission / allowance |
| base_amount | DECIMAL(18,2) DEFAULT 0 | |
| rate | DECIMAL(8,2) DEFAULT 0 | |
| amount | DECIMAL(18,2) | |
| description | VARCHAR(500) NULL | |

Bảng này giúp truy ngược: "lương tháng này được tính từ những chuyến nào?".

---

# 11. FILE / CHỨNG TỪ ĐÍNH KÈM

## attachments

Dùng polymorphic để lưu hợp đồng scan, hóa đơn, bằng lái, ảnh chứng từ...

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| attachable_type | VARCHAR(255) | App\Models\Contract |
| attachable_id | BIGINT UNSIGNED | |
| file_name | VARCHAR(255) | |
| file_path | VARCHAR(1000) | |
| mime_type | VARCHAR(100) NULL | |
| file_size | BIGINT UNSIGNED NULL | |
| uploaded_by | BIGINT UNSIGNED FK NULL | users.id |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

---

# 12. BẢO DƯỠNG XE – NÊN CÓ, NHƯNG CÓ THỂ LÀ PHASE 2

## vehicle_maintenances

| Variable | Type | Example / Note |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| vehicle_id | BIGINT UNSIGNED FK | |
| partner_id | BIGINT UNSIGNED FK NULL | Gara |
| maintenance_type | VARCHAR(50) | periodic / repair |
| started_at | DATETIME | |
| completed_at | DATETIME NULL | |
| odometer | INT UNSIGNED NULL | |
| cost | DECIMAL(18,2) DEFAULT 0 | |
| status | VARCHAR(30) | planned / in_progress / completed / cancelled |
| description | TEXT NULL | |
| created_at | TIMESTAMP NULL | |
| updated_at | TIMESTAMP NULL | |

Khi xe ở trạng thái bảo dưỡng, không cho phân vào `trip_assignments`.

---

# 13. SƠ ĐỒ QUAN HỆ NGHIỆP VỤ CHÍNH

```text
customers
   |
   +----< rental_requests ----< rental_request_items
   |             |
   |             +----< quotations ----< quotation_items
   |                          |
   +--------------------------+
   |
   +----< contracts ----< contract_items
                            |
                            +----< contract_schedule_rules
                            |          |
                            |          +----< contract_schedule_days
                            |
                            +----< trip_schedules
                                      |
                                      +----< trip_assignments >---- vehicles
                                      |          |
                                      |          +---------------> drivers
                                      |
                                      +---- dispatch_orders
                                                |
                                                +---- driver_attendances
                                                |
                                                +---- expenses

customers ----< receipts
partners  ----< partner_payments
drivers   ----< driver_advances

driver_attendances
       |
       +----< payroll_item_details >---- payroll_items >---- payrolls
```

---

# 14. LUỒNG DỮ LIỆU ĐỀ XUẤT

```text
Yêu cầu thuê xe
      |
      v
rental_requests
      |
      v
quotations
      |
      v
contracts
      |
      +---------------------------+
      |                           |
Hợp đồng cố định              Hợp đồng du lịch
      |                           |
schedule_rules                    |
      |                           |
      +-----------+---------------+
                  |
                  v
            trip_schedules
                  |
           kiểm tra trùng lịch
                  |
                  v
           trip_assignments
           /              \
       vehicle           driver
                  |
                  v
           dispatch_orders
                  |
                  v
              completed
             /    |     \
            /     |      \
           v      v       v
     attendance expenses revenue
           |
           v
        payroll
```

---

# 15. QUY TẮC CHỐNG TRÙNG LỊCH

Một xe hoặc tài xế không được có 2 `trip_schedules` giao nhau nếu assignment đang còn hiệu lực.

Điều kiện overlap:

```sql
existing_start < new_end
AND existing_end > new_start
```

Khi phân xe:

```text
1. Lấy scheduled_start_at / scheduled_end_at của trip.
2. Tìm assignment hiện hành của vehicle.
3. Join trip_schedules.
4. Nếu có lịch overlap -> reject.
5. Làm tương tự với driver.
6. Thực hiện trong transaction để tránh 2 điều hành viên cùng phân một xe.
```

Không nên chỉ dựa vào `vehicles.operational_status = available`, vì một xe có thể rảnh hiện tại nhưng đã có lịch vào ngày mai.

---

# 16. CÔNG NỢ KHÔNG CẦN BẢNG RIÊNG Ở GIAI ĐOẠN ĐẦU

## Công nợ khách hàng

```text
opening_balance
+ doanh thu/hợp đồng phải thu
- receipts
= customer_receivable
```

## Công nợ chủ xe / đối tác

```text
opening_balance
+ partner_vehicle_cost / khoản phải trả
- partner_payments
= partner_payable
```

Báo cáo công nợ nên query từ nguồn phát sinh thay vì lưu một cột `debt` rồi phải đồng bộ ở nhiều nơi.

---

# 17. BÁO CÁO KHÔNG NHẤT THIẾT CẦN BẢNG RIÊNG

Các báo cáo sau có thể dựng từ query/view:

- Xe chạy công nhân
- Xe chạy du lịch
- Bảng lương lái xe
- Công nợ khách hàng
- Công nợ chủ xe
- Doanh thu theo tháng
- Chi phí theo tháng
- Lợi nhuận theo tháng
- Hiệu suất xe
- Số chuyến theo tài xế

Ví dụ lợi nhuận tháng:

```text
Revenue
- Vehicle/Trip Expenses
- Partner Vehicle Costs
- Driver Payroll / Driver Trip Costs
= Profit
```

Chỉ tạo bảng snapshot báo cáo khi có yêu cầu "khóa sổ" và phải giữ nguyên kết quả lịch sử.

---

# 18. PERMISSION ĐỀ XUẤT

## User / Role

```text
user.view
user.create
user.update
user.disable
user.assign_role

role.view
role.create
role.update
role.delete
role.assign_permission
```

## Customer

```text
customer.view
customer.create
customer.update
customer.delete
```

## Partner

```text
partner.view
partner.create
partner.update
partner.delete
```

## Vehicle / Driver / Route

```text
vehicle.view
vehicle.create
vehicle.update
vehicle.delete

driver.view
driver.create
driver.update
driver.delete

route.view
route.create
route.update
route.delete
route_rate.manage
```

## Rental / Quotation / Contract

```text
rental_request.view
rental_request.create
rental_request.update

quotation.view
quotation.create
quotation.update
quotation.approve

contract.view
contract.create
contract.update
contract.approve
contract.cancel
```

## Dispatch

```text
schedule.view
schedule.create
schedule.update

dispatch.view
dispatch.create
dispatch.assign
dispatch.replace_vehicle
dispatch.replace_driver
dispatch.complete
dispatch.cancel
```

## Finance / Payroll

```text
receipt.view
receipt.create
receipt.update

expense.view
expense.create
expense.update

partner_payment.view
partner_payment.create

advance.view
advance.create
advance.approve

payroll.view
payroll.calculate
payroll.approve
payroll.pay
```

## Report

```text
report.operation
report.payroll
report.customer_debt
report.partner_debt
report.profit
```

---

# 19. GỢI Ý PHÂN ROLE -> PERMISSION

## admin

Tất cả permission.

## director

- Xem toàn bộ danh mục/nghiệp vụ
- `quotation.approve`
- `contract.approve`
- `payroll.view`
- `report.*`
- Xem tài chính/công nợ/lợi nhuận
- Không nhất thiết trực tiếp tạo/sửa nghiệp vụ hằng ngày

## sales

- customer.*
- rental_request.*
- quotation.view/create/update
- contract.view/create/update
- route.view
- vehicle.view để kiểm tra khả năng đáp ứng

## dispatcher

- customer.view
- contract.view
- vehicle.view
- driver.view
- route.view
- schedule.*
- dispatch.*

## accountant

- customer.view
- partner.view
- contract.view
- dispatch.view
- receipt.*
- expense.*
- partner_payment.*
- advance.*
- payroll.*
- report.payroll
- report.customer_debt
- report.partner_debt
- report.profit

## driver

Chỉ dữ liệu của chính mình:

- dispatch.view-own
- dispatch.update-own
- dispatch.complete-own
- expense.create-own
- payroll.view-own

Các quyền `*-own` cần Policy kiểm tra `driver.user_id == auth()->id()` chứ không chỉ dựa vào middleware permission.

---

# 20. NHỮNG ĐIỂM NÊN SỬA TRONG DATABASE HIỆN TẠI

1. `users.password` đã dùng đúng convention Laravel.
2. `users.user_name` -> `username` hoặc dùng `name`; không nên dùng tên không nhất quán.
3. `roles` / `permissions`: giữ schema do Spatie tạo, không cần tự thêm `is_active`, `user_name_created`, `user_name_updated`.
4. Database hiện tại còn thiếu `role_has_permissions`.
5. `user_name_created` / `user_name_updated` -> ưu tiên `created_by` / `updated_by` FK `users.id`.
6. `customers.isactive` -> `is_active BOOLEAN`.
7. Tiền luôn dùng `DECIMAL(18,2)`, không dùng `INT`.
8. `vehicles.year` -> `manufacture_year SMALLINT`.
9. `vehicle_types.tour_driver_rate` -> `tour_driver_commission_rate DECIMAL(5,2)` vì nghiệp vụ là phần trăm.
10. `route_rates.Driver_wape` -> `driver_wage`.
11. Không dùng cột chung chung `Metadata` nếu chưa có mục đích rõ ràng.
12. `drivers.partner_id` phải nullable vì tài xế công ty không có partner.
13. Trạng thái xe không được dùng thay cho lịch. Chống trùng phải dựa trên `trip_schedules` + `trip_assignments`.
14. Không tạo bảng `customer_debts`/`partner_debts` ngay từ đầu nếu có thể tính từ phát sinh và thanh toán.
15. Báo cáo nên query/view, không nhân bản dữ liệu sang các bảng báo cáo.

---

# 21. THỨ TỰ MIGRATION NÊN TẠO

```text
01 users

02 roles / permissions / model_has_* / role_has_permissions

03 customers
04 partners
05 vehicle_types
06 vehicles
07 drivers
08 routes
09 route_rates
10 expense_types

11 rental_requests
12 rental_request_items
13 quotations
14 quotation_items

15 contracts
16 contract_items
17 contract_schedule_rules
18 contract_schedule_days

19 trip_schedules
20 trip_assignments
21 dispatch_orders
22 driver_attendances

23 receipts
24 expenses
25 partner_payments
26 driver_advances

27 payrolls
28 payroll_items
29 payroll_item_details

30 attachments
31 vehicle_maintenances (optional / phase 2)
```

---

# 22. MVP NẾU MUỐN HOÀN THÀNH NHANH

Nếu thời gian dự án ngắn, triển khai trước:

```text
users + Spatie
customers
partners
vehicle_types
vehicles
drivers
routes
route_rates

rental_requests
contracts
contract_items

trip_schedules
trip_assignments
dispatch_orders

receipts
expenses
partner_payments

driver_attendances
driver_advances
payrolls
payroll_items
```

Có thể để `quotations`, `contract_schedule_rules`, `attachments`, `vehicle_maintenances`, `payroll_item_details` cho giai đoạn tiếp theo nếu deadline không cho phép.
