# Backend task — API nghiệp vụ HaLinhTravel

> Tài liệu này là đặc tả API nghiệp vụ cho backend HaLinhTravel. Tài liệu không mô tả API xác thực, JWT, tài khoản, vai trò hoặc phân quyền.

> **Quy ước thay thế (Dispatch và bán hàng, 08/10/2026):** Khi mâu thuẫn với phần mô tả cũ bên dưới, phần này là chuẩn áp dụng.
>
> - `Rental Request → Quotation → Contract trip` là một luồng bắt buộc. Báo giá mới luôn có `rental_request_id` và `valid_until`; báo giá chỉ thay đổi thông tin thương mại, không được đổi loại xe/tuyến/số lượng của yêu cầu. Khi một báo giá được chấp nhận, request là `accepted`, các phương án mở còn lại là `superseded`; chỉ reject request mới đóng toàn bộ các phương án. Báo giá quá hạn là `expired` và token email cũng mất hiệu lực.
> - `POST /api/contract/contracts` chỉ tạo hợp đồng `principle`, không nhận request/quotation. `POST /api/contract/contracts/from-quotation` tạo `trip` duy nhất từ quotation `approved` của request `accepted`; ngày hiệu lực phải bao phủ toàn bộ khoảng thuê.
> - Kích hoạt hợp đồng `trip` tự tạo một `TripSchedule` `PLANNED` cho **mỗi** đơn vị quantity của từng item, theo thời gian request. Generator schedule-rule cũng tạo đủ quantity và idempotent. Một schedule là một xe.
> - Dispatch dùng các route `/api/dispatch/trip-schedules`, `/api/dispatch/orders` và `/api/dispatch/my-orders` (không dùng path cũ `dispatch-orders`). Assignment bắt buộc `vehicle_id`, `driver_id`; backend suy ra `partner_id` từ xe. Không được thay assignment khi tồn tại order chưa hủy.
> - Lifecycle order: `ISSUED → ASSIGNED → IN_PROGRESS → PENDING_CONFIRMATION → COMPLETED`; chỉ dispatcher confirm completion. Driver chỉ được start/report qua `/my-orders`, sau khi ownership `User.user_name → Driver.user_name → assignment.driver_id` được xác thực. Report không có tiền; dispatcher nhập tiền khi confirm, kiểm tra ODO/thời gian và cập nhật ODO xe. Có thể return báo cáo về `IN_PROGRESS` cùng `review_note`.
> - Có thể hủy `ISSUED`/`ASSIGNED`; sau đó thay assignment và phát hành order mới. Migration Dispatch bỏ unique `trip_schedule_id` để giữ lịch sử order đã hủy.

## 1. Quy ước chung

### 1.1. URL, dữ liệu và response

- Base URL là `/api`. Mỗi endpoint nằm dưới prefix module của nó:

  | Module | Prefix |
  | --- | --- |
  | `MasterData` | `/api/master-data` |
  | `Rental` | `/api/rental` |
  | `Contract` | `/api/contract` |
  | `Dispatch` | `/api/dispatch` |
  | `Finance` | `/api/finance` |
  | `DriverPayroll` | `/api/driver-payroll` |
  | `Other` | `/api/other` |

- Request và response JSON dùng `snake_case`. Upload file dùng `multipart/form-data`.
- `POST` dùng để tạo resource và thực hiện action; `PATCH` cập nhật phần dữ liệu được phép; `DELETE` ngừng sử dụng resource khi nghiệp vụ cho phép.
- Response thành công giữ chuẩn hiện có của dự án:

  ```json
  { "message": "...", "metadata": {} }
  ```

  Tạo mới trả HTTP `201`; đọc, cập nhật, action và deactivate trả HTTP `200`.
- Response lỗi dùng chuẩn middleware hiện có:

  ```json
  {
    "message": "...",
    "status_code": 422,
    "metadata": { "field": ["..."] },
    "path": "/api/...",
    "timestamp": "2026-10-01T00:00:00.000000Z"
  }
  ```

  Dùng `404` cho resource không có, `409` cho xung đột trạng thái/lịch/khóa dữ liệu, và `422` cho dữ liệu không hợp lệ.
- Các endpoint collection trả toàn bộ tập dữ liệu phù hợp trong `metadata`; không nhận hoặc trả `page`, `per_page`, `total`, `last_page`. Frontend tự tìm kiếm, sắp xếp và phân trang.
- Endpoint detail trả đầy đủ các quan hệ cần hiển thị. Endpoint collection trả bản ghi chính và thông tin nhận diện tối thiểu của quan hệ như `id`, `code`, `name`, `license_plate`.
- `id`, timestamps, audit metadata, số chứng từ, trạng thái workflow, cờ khóa và các tổng/từng dòng do hệ thống tính không nhận từ client. Hệ thống tự gán hoặc tính trong transaction.
- Giá trị tiền là `decimal` và được serialize dưới dạng chuỗi. Ngày dùng `YYYY-MM-DD`, giờ dùng `HH:mm:ss`, thời điểm dùng ISO-8601.
- Mọi bản ghi có metadata chỉ được deactivate bằng `DELETE` (`is_active=false`), không hard-delete. Danh sách mặc định chỉ gồm bản ghi active. Bản ghi bị khóa hoặc đang được quy trình mở tham chiếu không được deactivate.

### 1.2. Quy tắc dữ liệu dùng chung

- Foreign key trong payload phải trỏ tới bản ghi active. Mọi mã và giá trị unique phải được kiểm tra trước khi tạo/cập nhật.
- Tiền, số lượng, ODO, km và giờ chờ không âm; quantity lớn hơn `0`; thời điểm kết thúc không sớm hơn thời điểm bắt đầu.
- Không cập nhật trực tiếp trạng thái workflow qua `PATCH`; chỉ action được liệt kê bên dưới mới thay đổi trạng thái.
- Action thay đổi trạng thái, thay thế phân công, sinh dữ liệu hàng loạt, tính lương và khóa sổ phải chạy trong transaction và trả resource sau thay đổi.

## 2. Module `MasterData`

Prefix: `/api/master-data`.

| Resource | Endpoint | Payload create/update | Ràng buộc chính |
| --- | --- | --- | --- |
| Customers | `GET, POST /customers`; `GET, PATCH, DELETE /customers/{id}` | `code`, `type` (`individual`, `company`), `name`, `phone`, `email`, `cccd`, `tax_code`, `address`, `contact_name`, `opening_balance` | `code` unique; không deactivate khi còn chứng từ hay quy trình mở. |
| Partners | `GET, POST /partners`; `GET, PATCH, DELETE /partners/{id}` | `code`, `type` (`transport_company`, `vehicle_owner`, `garage`, `fuel_supplier`, `other`), liên hệ, CCCD/mã số thuế, địa chỉ, `bank_name`, `bank_account`, `opening_balance` | `code` unique; không deactivate khi còn xe, tài xế, chi phí hoặc thanh toán mở. |
| Vehicle types | `GET, POST /vehicle-types`; `GET, PATCH, DELETE /vehicle-types/{id}` | `code`, `name`, `seats`, `tour_driver_commission_rate` | `code` unique; `seats > 0`; commission không âm. |
| Vehicles | `GET, POST /vehicles`; `GET, PATCH, DELETE /vehicles/{id}` | `license_plate`, `vehicle_type_id`, `ownership_type` (`company`, `partner`), `partner_id`, `brand`, `model`, `manufacture_year`, `current_odometer`, `vehicle_status` (`available`, `assigned`, `maintenance`, `inactive`), `notes` | Biển số unique; xe `partner` cần `partner_id`, xe `company` không có `partner_id`; không chuyển xe có lịch hiện hành sang `maintenance`/`inactive`. |
| Drivers | `GET, POST /drivers`; `GET, PATCH, DELETE /drivers/{id}` | `code`, `user_name`, `partner_id`, `type` (`company`, `partner`), `full_name`, `phone`, `cccd`, `license_number`, `license_class`, `license_issued_at`, `license_expired_at`, `base_salary`, `responsibility_allowance`, `joined_at`, `left_at` | `code`, `user_name` (nếu có), `cccd` (nếu có) và `license_number` unique; tài xế `partner` cần `partner_id`; không phân công tài xế đã nghỉ hoặc giấy phép hết hạn. |
| Routes | `GET, POST /routes`; `GET, PATCH, DELETE /routes/{id}` | `code`, `customer_id`, `name`, `shift_name`, `pickup_location`, `dropoff_location`, `default_pickup_time`, `default_return_time`, `estimated_distance_km` | `code` unique; `customer_id` là tùy chọn. |
| Route rates | `GET, POST /route-rates`; `GET, PATCH, DELETE /route-rates/{id}`; `GET /route-rates/lookup?route_id=&vehicle_type_id=&at_date=` | `route_id`, `vehicle_type_id`, `customer_price`, `driver_wage`, `effective_from`, `effective_to` | Unique theo `route_id`, `vehicle_type_id`, `effective_from`; không cho khoảng hiệu lực chồng lấn; lookup trả giá hiệu lực tại ngày yêu cầu. |
| Expense types | `GET, POST /expense-types`; `GET, PATCH, DELETE /expense-types/{id}` | `code`, `name`, `scope` (`vehicle`, `trip`, `general`) | `code` unique; không deactivate khi còn chi phí chưa khóa. |

Collection của `vehicles`, `drivers`, `routes`, `customers`, `partners`, `vehicle-types` và `expense-types` phải đủ dữ liệu cho select/search ở client; không tạo endpoint dropdown riêng.

## 3. Module `Rental`

Prefix: `/api/rental`.

### 3.1. Yêu cầu thuê xe

| Endpoint | Payload / response | Nghiệp vụ |
| --- | --- | --- |
| `GET /requests`; `GET /requests/{id}` | Trả request, customer, thông tin hành trình và `items` với `vehicle_type_name`, `route_name`. | Cần quyền `rental-requests.view`. |
| `POST /requests` | `customer_id`, `source` nullable, `requested_at`, `service_type` (`fixed`, `tourism`, `school`, `business`), địa điểm/thời gian, `note`, `items[]` với `vehicle_type_id`, `quantity`, `route_id`, `note`. | Sinh `request_no`, trạng thái `new`; tạo master-detail trong transaction. Cần `rental-requests.manage`. |
| `PATCH /requests/{id}` | Chỉ gửi field cần đổi; gửi `items[]` sẽ thay toàn bộ items. Không nhận `request_no`/`status`. | Chỉ `new` được sửa; trạng thái khác trả `409`. |
| `DELETE /requests/{id}` | Không có payload. | Chỉ `new` được ngừng hoạt động (`is_active=false`); trạng thái khác trả `409`. |
| `POST /requests/{id}/mark-quoted` | Không có payload. | Chỉ `new -> quoted` khi request có ít nhất một quotation `sent`. Gửi quotation tự chuyển request `new -> quoted`. |
| `POST /requests/{id}/accept` | Không có payload. | Action nội bộ, chỉ `quoted -> accepted`. Luồng chuẩn là khách chấp nhận trong email. |
| `POST /requests/{id}/reject` | Không có payload. | Action nội bộ, `new` hoặc `quoted -> rejected`. |

`customer_id`, `vehicle_type_id` và `route_id` phải là bản ghi active. Mỗi item có `quantity > 0`; `end_at` không trước `start_at`.

Một Rental Request hiện đại diện cho **một hành trình chung** và có hai cách nhập:

- **Theo tuyến:** tất cả `items[].route_id` phải cùng một giá trị khác `null`. Route phải là route chung (`customer_id = null`) hoặc thuộc customer của request. Backend snapshot `pickup_location`/`dropoff_location` từ route và không tin địa điểm client gửi.
- **Nhập hành trình:** mọi `items[].route_id` là `null`; bắt buộc `pickup_location`, `dropoff_location`, `start_at`. `end_at` là tùy chọn.

Không chấp nhận mode hỗn hợp hoặc nhiều route khác nhau; trả `422`.

### 3.2. Báo giá

| Endpoint | Payload / response | Nghiệp vụ |
| --- | --- | --- |
| `GET /quotations`; `GET /quotations/{id}` | Trả customer, request, item, route/vehicle type và các tổng tiền. | Cần quyền `quotations.view`. |
| `POST /quotations` | `customer_id`, `rental_request_id` nullable, `quotation_date`, `valid_until` nullable, `discount_amount`, `payment_terms`, `items[]` gồm `route_id`, `vehicle_type_id`, `description`, `quantity`, `unit_price`. | Sinh `quotation_no`, tạo trạng thái `draft`. Cần `quotations.manage`. |
| `PATCH /quotations/{id}` | PATCH các field tạo; nếu gửi `items[]` thì thay toàn bộ items. | Chỉ `draft` được sửa. Backend kiểm tra lại customer/request, item active, ngày hiệu lực và tính lại tiền. |
| `DELETE /quotations/{id}` | Không có payload. | Chỉ `draft` được xóa; trạng thái khác trả `409`. |
| `POST /quotations/{id}/send` | Không có payload. | `draft -> sent`; customer phải có email, báo giá chưa hết hiệu lực, `FRONTEND_QUOTATION_RESPONSE_URL` và queue/SMTP phải sẵn sàng. Tạo token phản hồi và queue email sau commit. |
| `POST /quotations/{id}/expire` | Không có payload. | Chỉ `sent -> expired`; Rental Request liên quan vẫn `quoted` để có thể lập báo giá khác. |

`customer_id`, `vehicle_type_id` và `route_id` phải active. Nếu có `rental_request_id`, customer của báo giá phải trùng customer của request; request chỉ được dùng khi `new` hoặc `quoted`. Client **không** gửi `quotation_no`, `status`, `amount`, `subtotal` hoặc `total_amount`: backend tính `amount = quantity × unit_price`, `subtotal` và `total_amount = subtotal - discount_amount`; `discount_amount` không được lớn hơn subtotal.

### 3.3. Phản hồi báo giá từ email

Prefix public: `/api/public/quotation-responses`. Các endpoint này không dùng JWT/Bearer token nhưng có rate limit.

| Endpoint | Payload / response | Nghiệp vụ |
| --- | --- | --- |
| `GET /{token}` | Trả summary an toàn cho customer: số báo giá, customer, dates, items, tiền, điều khoản, status. | Frontend customer-response gọi trước khi hiển thị giao diện. Không trả ID nội bộ. |
| `POST /{token}/accept` | Không có payload. | Dùng token một lần: quotation `sent -> approved`, Rental Request `quoted -> accepted`; lưu `approved_at`, `customer_responded_at`. |
| `POST /{token}/reject` | `note` nullable, tối đa 2.000 ký tự. | Dùng token một lần: quotation `sent -> rejected`, Rental Request `quoted -> rejected`; lưu thời điểm và note phản hồi. |

Khi gửi email, backend sinh token ngẫu nhiên, chỉ lưu hash và tạo link `${FRONTEND_QUOTATION_RESPONSE_URL}?token=...`. Token hết hạn vào cuối `valid_until`; nếu `valid_until` là `null`, hết hạn sau 7 ngày từ lúc gửi. Token sai, đã dùng, hết hạn, hoặc báo giá không còn `sent` trả `410`. Customer chỉ chọn accept **hoặc** reject; thao tác thành công làm token không thể dùng lại.

## 4. Module `Contract`

Prefix: `/api/contract`.

### 4.1. Hợp đồng

| Endpoint | Payload / response | Nghiệp vụ |
| --- | --- | --- |
| `GET /contracts`; `GET /contracts/{id}` | Trả header, customer, Rental Request, Quotation, items và tổng tiền. | Cần `contracts.view`. |
| `POST /contracts` | `customer_id`, `rental_request_id` nullable, `quotation_id` nullable, `contract_type` (`trip`, `principle`), `signed_date` nullable, `effective_from`, `effective_to` nullable, `deposit_required`, `payment_terms`, `terms`, `items[]`. | Sinh `contract_no`, backend tính `total_amount`, trạng thái `draft`. Cần `contracts.manage`. |
| `POST /contracts/from-quotation` | `quotation_id`, `contract_type`, ngày ký/hiệu lực, đặt cọc và điều khoản. | Chỉ quotation `approved` có Rental Request `accepted`; sao chép customer, request, quotation, item và snapshot hành trình; `driver_wage` khởi tạo `0`; request chuyển `converted`. |
| `PATCH /contracts/{id}`; `DELETE /contracts/{id}` | PATCH field cần đổi; `items[]` nếu gửi sẽ thay toàn bộ items. | Chỉ `draft` được sửa/ngừng hoạt động; chặn khi đã có lịch chuyến hoặc phiếu thu. |
| `POST /contracts/{id}/activate` | Không có payload. | `draft -> active`; cần item hợp lệ và thời gian hiệu lực hợp lệ. |
| `POST /contracts/{id}/complete` | Không có payload. | `active -> completed` khi không còn chuyến mở. |
| `POST /contracts/{id}/cancel` | Không có payload. | `draft`/`active -> cancelled`; chặn khi có lịch chuyến hoặc phiếu thu. |

Mỗi `items[]` gồm `route_id` nullable, `vehicle_type_id`, `service_type`, `quantity`, `unit_price`, `driver_wage`, `pickup_location`, `dropoff_location`, `note`. `total_amount` là tổng `quantity × unit_price` do server tính; `deposit_required` không vượt tổng tiền. Customer, route và vehicle type phải active; `effective_to` không trước `effective_from`.

Một quotation chỉ có tối đa một Contract đang mở (`draft` hoặc `active`). Sau khi Contract cũ `cancelled`, có thể tạo Contract mới từ quotation đó.

### 4.2. Quy tắc lịch và lịch cố định

| Endpoint | Payload / response | Nghiệp vụ |
| --- | --- | --- |
| `GET, POST /contracts/{contract_id}/schedule-rules` | Create nhận `contract_item_id`, `route_id` nullable, `effective_from`, `effective_to`, `default_vehicle_id`, `default_driver_id`, `note`. | Chỉ Contract `active`; item phải thuộc Contract. Route là route chung hoặc thuộc customer của Contract. Xe/tài xế mặc định chỉ là gợi ý, không tạo assignment. |
| `GET /schedule-rules/{id}`; `PATCH, DELETE /schedule-rules/{id}` | Detail luôn gồm `days[]`, `trip_schedules_count`, `is_locked`. DELETE chỉ đặt `is_active=false`. | Không sửa, thay days hoặc deactivate rule đã sinh bất kỳ Trip Schedule nào; trả `409`. |
| `PUT /schedule-rules/{id}/days` | `days[]`: `weekday` (`Mon`…`Sun`), `pickup_time`, `return_time` nullable, `shift_name`. | Thay toàn bộ ngày lịch trong transaction; ít nhất một dòng, unique theo rule + weekday + pickup time; return time nếu có phải sau pickup time trong cùng ngày. |
| `POST /schedule-rules/{id}/generate-trip-schedules` | `from_date`, `to_date`; response có mảng `created`, `skipped`, `conflicts` và `summary`. | Khoảng ngày phải nằm trong hiệu lực rule/hợp đồng; sinh `PLANNED`. Cùng rule/thời điểm là `skipped`; lịch khác cùng item/thời điểm là `conflicts`. Day được sinh phải có `return_time`. |

## 5. Module `Dispatch`

Prefix: `/api/dispatch`.

### 5.1. Kiểm tra năng lực Sales (snapshot)

`POST /api/dispatch/availability` cần quyền `rental-capacity.view` (Sales, Dispatcher, Director, Admin). Payload:

```json
{
  "start_at": "2026-10-20T06:00:00+07:00",
  "end_at": "2026-10-20T18:00:00+07:00",
  "items": [
    { "vehicle_type_id": 4, "quantity": 2 },
    { "vehicle_type_id": 6, "quantity": 1 }
  ],
  "ownership_type": null,
  "partner_id": null
}
```

`end_at` là bắt buộc và phải sau `start_at`. `ownership_type` có thể là `company` hoặc `partner`; `partner_id` là filter độc lập tùy chọn. Khi không gửi filter, response trả cả breakdown xe/tài xế công ty và đối tác. Mỗi `vehicle_type_id` chỉ được xuất hiện một lần trong `items`.

Response có `vehicle_capacities[]` theo từng loại xe (số cần, số xe công ty/đối tác/tổng, `candidates`, `is_sufficient`), `driver_capacity` tổng hợp (tổng số lái xe cần bám theo tổng số xe) và `can_fulfill`. Xe được tính khi active, đúng loại và `vehicle_status=available`; tài xế phải active, đã vào làm, chưa nghỉ và bằng lái còn hạn tại `end_at`. Assignment `is_current=true` của schedule chưa `COMPLETED`/`CANCELLED` và giao thời gian sẽ loại cả xe lẫn tài xế đó.

API chỉ đọc snapshot: không tạo reservation, không đổi trạng thái xe/tài xế và không thay thế kiểm tra chặn khi phân công ở giai đoạn sau. Frontend gọi lại ngay trước khi gửi quotation hoặc tạo Contract, hiển thị thời điểm snapshot và cảnh báo rằng tài nguyên chưa được giữ.

### 5.2. Lịch chuyến và phân công

| Endpoint | Payload / response | Nghiệp vụ |
| --- | --- | --- |
| `GET /trip-schedules`; `GET /trip-schedules/{id}` | Detail gồm hợp đồng, item, rule, assignment hiện hành/lịch sử và dispatch order. | Trả toàn bộ lịch active. Status: `PLANNED`, `ASSIGNED`, `IN_PROGRESS`, `COMPLETED`, `CANCELLED`. |
| `POST /trip-schedules` | `contract_id`, `contract_item_id`, `schedule_rule_id`, `service_type`, `route_id`, `scheduled_start_at`, `scheduled_end_at`, `pickup_location`, `dropoff_location`, `journey`, `required_vehicle_type_id`, `note`. | Chỉ cho hợp đồng active; sinh `schedule_no`; mặc định `PLANNED`. |
| `PATCH /trip-schedules/{id}`; `DELETE /trip-schedules/{id}` | Các field tạo được phép sửa. | Chỉ schedule `PLANNED` chưa có dispatch order. |
| `POST /trip-schedules/{id}/cancel` | `note` tùy chọn. | `PLANNED`/`ASSIGNED -> CANCELLED`; chặn khi order đang chạy/đã hoàn thành. |
| `POST /availability` | `start_at`, `end_at`, `items[]` (`vehicle_type_id`, `quantity`), tùy chọn `ownership_type`, `partner_id`; trả `vehicle_capacities`, `driver_capacity`, `can_fulfill`. | Snapshot năng lực chung cho Sales/điều hành; không reservation, không có `exclude_trip_schedule_id`. |
| `GET /trip-schedules/{id}/assignments` | Trả đầy đủ lịch sử assignment, `is_current`, xe, tài xế, partner, lý do thay thế. | Có tối đa một assignment hiện hành. |
| `POST /trip-schedules/{id}/assignments` | `vehicle_id`, `driver_id`, `partner_id` tùy chọn. | Tạo assignment `PRIMARY`, `is_current=true`, chuyển schedule `PLANNED -> ASSIGNED`. |
| `POST /trip-schedules/{id}/assignments/substitute` | `vehicle_id`, `driver_id`, `partner_id` tùy chọn, `replace_reason`. | Đóng assignment hiện hành và tạo `SUBSTITUTE` liên kết assignment cũ trong một transaction. |
| `DELETE /trip-assignments/{id}` | Không có payload. | Chỉ bỏ assignment hiện hành khi chưa có dispatch order; schedule trở về `PLANNED`; không xóa lịch sử. |

Xe hoặc tài xế không được có assignment hiện hành cho hai schedule giao thời gian. Xe phải đúng loại xe yêu cầu; xe đối tác phải dùng đúng `partner_id` của xe. Tài xế không được nghỉ hoặc hết hạn bằng lái.

### 5.2. Lệnh điều xe

| Endpoint | Payload / response | Nghiệp vụ |
| --- | --- | --- |
| `GET /orders`; `GET /orders/{id}` | Detail trả schedule, assignment, customer, route và báo cáo vận hành. | Trả toàn bộ lệnh active. |
| `POST /trip-schedules/{id}/orders` | Không có payload. | Chỉ schedule `ASSIGNED` có assignment hiện hành; sinh `order_no`, chỉ có một lệnh chưa hủy cho một schedule, trạng thái `ISSUED`. |
| `POST /orders/{id}/assign` | Không có payload. | `ISSUED -> ASSIGNED`; xác nhận assignment hiện hành còn hợp lệ. |
| `POST /my-orders/{id}/start` | `actual_start_at`, `start_odometer`, `note` tùy chọn. | Chỉ tài xế sở hữu lệnh gọi; `ASSIGNED -> IN_PROGRESS`; ODO không được nhỏ hơn ODO hiện tại xe. |
| `POST /my-orders/{id}/report-completion` | `actual_end_at`, `end_odometer`, `actual_distance_km`, `waiting_hours`, `note`. | Chỉ tài xế sở hữu lệnh gọi; `IN_PROGRESS -> PENDING_CONFIRMATION`; không được sửa dữ liệu bắt đầu hay gửi trường tiền. |
| `POST /orders/{id}/confirm-completion` | `customer_amount`, `partner_vehicle_cost`, `external_driver_cost`, `note`. | Điều hành xác nhận `PENDING_CONFIRMATION -> COMPLETED`, kiểm tra ODO/thời gian và cập nhật ODO xe/schedule. |
| `POST /orders/{id}/return-completion` | `review_note`. | Điều hành trả `PENDING_CONFIRMATION -> IN_PROGRESS` để tài xế bổ sung. |
| `POST /orders/{id}/cancel` | `note` bắt buộc. | Chỉ `ISSUED`/`ASSIGNED`; đổi order thành `CANCELLED` và schedule về `ASSIGNED`. |

Không có `PATCH` trực tiếp cho dispatch order. `actual_end_at` không trước `actual_start_at`; `end_odometer` không nhỏ hơn `start_odometer`; các giá trị thực tế không âm.

## 6. Module `Finance`

Prefix: `/api/finance`.

| Resource | Endpoint | Payload create/update | Ràng buộc chính |
| --- | --- | --- | --- |
| Receipts | `GET, POST /receipts`; `GET, PATCH, DELETE /receipts/{id}`; `POST /receipts/{id}/lock` | `customer_id`, `contract_id` tùy chọn, `receipt_type` (`deposit`, `contract_payment`, `other`), `received_at`, `amount`, `payment_method` (`cash`, `bank_transfer`), `payer_name`, `description` | Sinh `receipt_no`; `deposit`/`contract_payment` cần contract cùng customer và tổng thu không vượt giá trị contract; record locked bất biến. Detail trả `contract_total`, `received_total`, `outstanding_amount`. |
| Expenses | `GET, POST /expenses`; `GET, PATCH, DELETE /expenses/{id}`; `POST /expenses/{id}/lock` | `expense_type_id`, `scope`, `vehicle_id`, `dispatch_order_id`, `partner_id`, `driver_id`, `expense_date`, `amount`, `payment_method`, `document_no`, `description` | Sinh `expense_no`; scope `vehicle` cần `vehicle_id`, `trip` cần `dispatch_order_id`, `general` không cần FK nghiệp vụ; record locked bất biến. |
| Partner payments | `GET, POST /partner-payments`; `GET, PATCH, DELETE /partner-payments/{id}`; `POST /partner-payments/{id}/lock` | `partner_id`, `dispatch_order_id` tùy chọn, `paid_at`, `amount`, `payment_method` (`cash`, `bank_transfer`), `description` | Sinh `payment_no`; nếu gắn order, partner phải đúng partner của assignment/xe đối tác của chuyến; record locked bất biến. |

## 7. Module `DriverPayroll`

Prefix: `/api/driver-payroll`.

### 7.1. Tạm ứng và chấm công

| Endpoint | Payload / response | Nghiệp vụ |
| --- | --- | --- |
| `GET, POST /advances`; `GET, PATCH, DELETE /advances/{id}` | `driver_id`, `advance_date`, `amount`, `description`. | Sinh `advance_no`, mặc định `pending`; amount không âm. |
| `POST /advances/{id}/confirm` | Không có payload. | `pending -> confirmed`; dữ liệu đã `payroll_locked` không sửa/deactivate. |
| `GET /attendances`; `GET /attendances/{id}` | Trả driver, dispatch order, `work_date`, `work_type`, `work_units`, `base_amount`, `rate`, `calculated_wage`, status. | Status: `pending`, `confirmed`, `payroll_locked`. |
| `POST /orders/{id}/attendance` *(Phase DriverPayroll)* | `work_units` tùy chọn cho chuyến `fixed`/`school`; `rate` tùy chọn cho `business`. | Chỉ order `COMPLETED`; mỗi order tối đa một attendance; lấy driver và ngày làm từ order/assignment. Không thuộc phạm vi core Dispatch hiện tại. |
| `PATCH /attendances/{id}`; `POST /attendances/{id}/confirm` | Patch chỉ các dữ liệu cho phép của attendance pending. | Confirm `pending -> confirmed`; không xóa attendance để giữ liên kết order. |

`fixed`/`school` tạo `work_type=fixed_trip`, lấy rate từ `contract_item.driver_wage`; `tourism` tạo `tourism_trip`, lấy base từ `customer_amount` và rate là commission của vehicle type; `business` tạo `other` và cần rate được gửi rõ ràng. Hệ thống tính `calculated_wage` từ base, work units và rate theo loại công.

### 7.2. Bảng lương

| Endpoint | Payload / response | Nghiệp vụ |
| --- | --- | --- |
| `GET, POST /payrolls`; `GET /payrolls/{id}`; `PATCH /payrolls/{id}` | Create: `month`, `year`, `from_date`, `to_date`; detail trả items và calculation details. | Sinh `code`, unique theo month/year, mặc định `draft`. |
| `POST /payrolls/{id}/calculate` | Không có payload. | Dùng attendance và advance `confirmed` trong kỳ; tạo lại items/details trong transaction; `draft`/`calculated -> calculated`. |
| `PATCH /payrolls/{id}/items/{item_id}` | `meal_allowance`, `other_allowance`, `deduction_amount`, `note`. | Chỉ payroll `calculated`; tính lại gross/net, không sửa thành phần tự tính. |
| `POST /payrolls/{id}/approve`; `POST /payrolls/{id}/mark-paid`; `POST /payrolls/{id}/lock` | Không có payload. | Luồng `calculated -> approved -> paid -> locked`; lock đóng băng payroll cùng attendance/advance nguồn. |

Payroll item gồm `base_salary`, `responsibility_allowance`, `meal_allowance`, `fixed_trip_wage`, `tourism_commission`, `other_allowance`, `advance_amount`, `deduction_amount`, `gross_salary`, `net_salary`, `note`. Detail chỉ lưu source attendance/order và các giá trị `calculation_type`, base, rate, amount do hệ thống tính.

## 8. Module `Other`

Prefix: `/api/other`.

### 8.1. Tệp đính kèm

| Endpoint | Payload / response | Nghiệp vụ |
| --- | --- | --- |
| `GET /attachments` | Trả toàn bộ attachment active cùng `attachable_type`, `attachable_id`, `file_name`, `mime_type`, `file_size` và URL tải. | Chỉ trả file của đối tượng cha còn được phép xem. |
| `POST /attachments` | Multipart: `file`, `attachable_type`, `attachable_id`. | Chỉ nhận parent: rental request, quotation, contract, dispatch order, receipt, expense, partner payment, advance. Hệ thống sinh/lưu path, metadata file và người upload; giới hạn MIME/kích thước theo cấu hình. |
| `GET /attachments/{id}/download`; `DELETE /attachments/{id}` | Không có payload. | Download kiểm tra parent; chỉ xóa khi parent chưa bị khóa. |

Client không gửi `file_name`, `file_path`, `mime_type`, `file_size` hoặc metadata upload.

### 8.2. Dashboard và báo cáo

Tất cả endpoint dưới đây chỉ đọc, không phân trang. Báo cáo nhận `from_date` và `to_date`; có thể nhận filter theo resource liên quan, và `format=json|csv` khi cần xuất. JSON trả dữ liệu bảng trong `metadata`, cùng `totals`/`chart` khi phù hợp.

| Endpoint | Dữ liệu trả về |
| --- | --- |
| `GET /dashboard` | KPI hiện tại: doanh thu, thu, chi, lợi nhuận, công nợ, số chuyến theo trạng thái, xe/tài xế sắp bận, giấy phép sắp hết hạn, schedule chưa điều xe. |
| `GET /reports/fixed-trips` | Chuyến `fixed`/`school`: route, customer, xe, tài xế, doanh thu, km và trạng thái. |
| `GET /reports/tourism-trips` | Chuyến `tourism`/`business`: hành trình, thời gian, xe, tài xế, doanh thu và chi phí. |
| `GET /reports/customer-debts` | Dư đầu kỳ, giá trị hợp đồng, phiếu thu và dư nợ theo customer/contract. |
| `GET /reports/partner-debts` | Dư đầu kỳ, chi phí phải trả, partner payment và dư nợ theo partner. |
| `GET /reports/cashflow` | Thu, chi phí, thanh toán partner, tạm ứng và tổng thu-chi theo ngày/tháng/phương thức. |
| `GET /reports/driver-payroll` | Payroll cùng detail nguồn tính, lọc theo kỳ/tài xế/trạng thái. |
| `GET /reports/expenses` | Chi phí theo type, scope, xe, chuyến, partner và chứng từ. |
| `GET /reports/profit-loss` | Doanh thu, chi phí vận hành, thanh toán partner, lương và lợi nhuận theo tháng. Response phải nêu `basis` là `accrual` hoặc `cash`. |

## 9. Ma trận vòng đời

| Đối tượng | Chuyển trạng thái hợp lệ |
| --- | --- |
| Rental request | `new -> quoted -> accepted -> converted`; `new/quoted -> rejected` |
| Quotation | `draft -> sent -> approved/rejected/expired` |
| Contract | `draft -> active -> completed`; `draft/active -> cancelled` |
| Trip schedule | `PLANNED -> ASSIGNED -> IN_PROGRESS -> COMPLETED`; `PLANNED/ASSIGNED -> CANCELLED` |
| Dispatch order | `ISSUED -> ASSIGNED -> IN_PROGRESS -> PENDING_CONFIRMATION -> COMPLETED`; `PENDING_CONFIRMATION -> IN_PROGRESS` khi trả báo cáo; `ISSUED/ASSIGNED -> CANCELLED` |
| Driver attendance / advance | `pending -> confirmed -> payroll_locked` |
| Payroll | `draft -> calculated -> approved -> paid -> locked` |

Các trạng thái `locked` hoặc `payroll_locked` là bất biến: không sửa, deactivate hay đảo ngược bằng API nghiệp vụ. Khi state precondition không đúng hoặc dữ liệu vừa bị thay đổi bởi thao tác khác, API trả `409`.
