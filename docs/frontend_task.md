# Frontend task — HaLinhTravel

> Đặc tả này là nguồn triển khai chính thức cho ứng dụng web nội bộ HaLinhTravel. Nó bao gồm nền tảng xác thực/quản trị đang có và các module nghiệp vụ sẽ được xây dựng. Hợp đồng nghiệp vụ mục tiêu lấy từ `D:\Laravel\halinhtravel-api\docs\backend_task.md`; tài liệu đó quyết định URL, phương thức, payload, response, enum và quy tắc workflow của các module nghiệp vụ.

## 1. Phạm vi, nguồn sự thật và hiện trạng

### 1.1. Phạm vi

- Người dùng: kinh doanh, điều hành, kế toán, quản lý và tài xế. Đây là workspace vận hành xe/tuyến, không phải trang đặt vé hay theo dõi hành trình cho khách hàng.
- Desktop là trải nghiệm chính. Mobile chỉ cam kết cho luồng tài xế khi backend đã có API ownership; các trang quản trị khác hiện rõ yêu cầu dùng màn hình desktop.
- Không xây GPS, bản đồ, định vị, telemetry xe hoặc tính năng theo dõi trực tiếp.
- Tài liệu này không thay đổi API, type runtime hay mã nguồn. Các route nghiệp vụ chưa hiện diện trong source backend là mục tiêu triển khai theo `backend_task.md`, không phải endpoint đã hoạt động.

### 1.2. Nền tảng frontend hiện có

| Hạng mục | Hiện trạng đã xác minh | Yêu cầu giữ khi mở rộng |
| --- | --- | --- |
| Stack | Vue 3, TypeScript, Vite, Tailwind CSS v4, Pinia, Vue Router, TanStack Vue Query/Table, Axios và Zod | Dùng `<script setup lang="ts">`, alias `@/` và type theo module. |
| Xác thực | `auth` Pinia store lưu access token; Axios tự gắn Bearer token, refresh một lần khi nhận `401`, rồi điều hướng về đăng nhập nếu hết phiên | Không nhân bản logic refresh ở service/module khác. |
| Phân quyền | Router guard kiểm tra `meta.permission` từ `current_user.permissions`; có trang 403 | Menu, route và action đều kiểm tra permission; backend vẫn là nơi kết luận `403`. |
| HTTP | `httpService` unwrap response `{ message, metadata }`; `ApiError` chuẩn hóa message và status | Mở rộng để map lỗi field `422`, conflict `409` và download/upload mà không đổi response backend. |
| Dữ liệu | Vue Query cho query/mutation; `DataGrid` là component thuần hiển thị, không gọi API | Page/composable sở hữu query key, URL state, toast và invalidation. |
| Giao diện | `src/assets/style.css` chứa token, Be Vietnam Pro, sidebar/route loading; BaseLayout có sidebar, header và tab | Dùng semantic token (`bg-card`, `text-primary`, `border-border`), không thêm palette riêng lẻ. |

### 1.3. Quy ước API và dữ liệu

- Base URL là `/api`. Các service dùng `VITE_API_BASE_URL` đã bao gồm base URL này; path trong code không lặp `/api`.
- Thành công có dạng `{ "message": "...", "metadata": ... }`. Lỗi dùng `message`, `status_code`, `metadata` (lỗi field), `path`, `timestamp` theo backend.
- Ngày: `YYYY-MM-DD`; giờ: `HH:mm:ss`; thời điểm: ISO-8601. Tiền là chuỗi decimal từ backend: hiển thị/nhập bằng decimal string, không cộng trừ bằng JavaScript `number`.
- Với **module nghiệp vụ**, endpoint collection trả toàn bộ bản ghi phù hợp trong `metadata`, không có `page`, `per_page`, `total` hoặc `last_page`. Frontend lọc, sắp xếp và phân trang tại client; DataGrid chạy client mode. Không gửi query phân trang server.
- Auth/Quản trị là ngoại lệ hiện hữu: API có cả endpoint phân trang và endpoint `/all`; frontend hiện dùng endpoint `/all` cho dropdown/bảng nhỏ. Chỉ đổi sang endpoint phân trang khi có yêu cầu riêng.
- Không gửi `id`, audit metadata, mã chứng từ, workflow state, flags khóa hoặc tổng tiền/dòng tiền do server tính. Sau mutation, lấy resource/tổng từ response hoặc refetch.

### 1.4. Prefix module mục tiêu

| Module | Prefix |
| --- | --- |
| Auth và quản trị | `/api/auth` |
| MasterData | `/api/master-data` |
| Rental | `/api/rental` |
| Contract | `/api/contract` |
| Dispatch | `/api/dispatch` |
| Finance | `/api/finance` |
| DriverPayroll | `/api/driver-payroll` |
| Other | `/api/other` |

Trong các bảng task, path đầu tiên luôn nêu đủ prefix module. Các path rút gọn sau dấu chấm phẩy hoặc trong cùng một ô kế thừa prefix đó; ví dụ `/api/rental/requests` và `/requests/{id}` lần lượt là `/api/rental/requests` và `/api/rental/requests/{id}`.

## 2. Quy ước trải nghiệm dùng chung

### 2.1. Khung vận hành và trạng thái

- Sidebar nhóm: Tổng quan, Bán hàng, Điều hành, Tài chính–nhân sự, Báo cáo, Danh mục và Quản trị. Nhóm/mục chỉ hiện khi user có permission `.view` phù hợp; thao tác ghi cần `.manage`.
- Header hiển thị người dùng hiện tại, breadcrumb và tab đang mở. Trang điều hành, dashboard, chi tiết lệnh và báo cáo tái sử dụng route strip/timeline ưu tiên giờ, tuyến, biển số, tài xế và trạng thái.
- Nhãn trạng thái hiển thị bằng tiếng Việt kèm màu, dùng nhất quán: `Chờ phân công`, `Đã xác nhận`, `Đang chạy`, `Hoàn tất`, `Cần đối soát`, `Quá hạn`. Raw enum từ API được map tập trung để vẫn phân biệt lifecycle chi tiết trong tooltip/accessible text.
- Biển số, thời gian và tiền dùng tabular numerals, dễ quét trong bảng. Màu trạng thái dùng token: primary cho điều hành, success cho hoàn tất, amber cho cần chú ý, destructive cho quá hạn/lỗi nghiêm trọng.

### 2.2. Loading, lỗi và mutation

- Mỗi list, dashboard widget, timeline và chart có skeleton tải đầu; đổi filter chỉ hiện loading nhỏ và giữ dữ liệu cũ khi hợp lý. Nút submit/action bị khóa trong lúc pending.
- Empty state nêu rõ không có dữ liệu gì và chỉ hiện CTA tạo mới nếu có quyền manage. Error state hiển thị `message`, có nút thử lại, không xóa dữ liệu form đang nhập.
- `401`: thực hiện refresh theo interceptor; refresh thất bại thì xóa session và quay về đăng nhập. `403`: trang 403 hoặc toast theo ngữ cảnh. `404`: thông báo bản ghi không còn tồn tại. `409`: giữ form/lựa chọn, thông báo conflict và refetch detail/availability. `422`: gắn `metadata` vào field hoặc dòng master-detail tương ứng.
- Deactivate, cancel, approve, lock, mark paid và thao tác không đảo ngược phải có dialog nêu hậu quả. Sau mutation, invalidate/refetch mọi list, detail, route strip, dashboard và report bị ảnh hưởng.

### 2.3. Component và form tái sử dụng

| Task | Yêu cầu hoàn thành |
| --- | --- |
| FND-01 — App shell và guard | Chuẩn hóa sidebar/route metadata theo `.view`, action theo `.manage`, 403 có đường quay về dashboard và URL query được giữ qua reload/back. |
| FND-02 — Data/API layer | Service module dùng prefix ở phần 1.4; Vue Query key theo resource + filter; query invalidation sau mutation; hỗ trợ multipart và download CSV/file. |
| FND-03 — DataGrid và filter bar | Dùng client filtering/sorting/pagination cho collection nghiệp vụ; filter có search, trạng thái, khoảng ngày, reset và đồng bộ URL query. Không giả định server pagination. |
| FND-04 — Form nghiệp vụ | Validation client chỉ hỗ trợ UX; backend là quyết định cuối. Có money input decimal, date/time input, select từ collection danh mục, lỗi `422` theo field/dòng và xác nhận rời form bẩn. |
| FND-05 — Attachment widget | Dùng với resource được backend cho phép: list, upload multipart, progress, download và delete theo permission/trạng thái đối tượng cha. |

## 3. Auth và quản trị truy cập

Đây là phần đã tồn tại và tiếp tục thuộc phạm vi frontend. Các endpoint bên dưới là hợp đồng Auth hiện có, độc lập với các endpoint nghiệp vụ ở `backend_task.md`.

| Task | Permission/API | Màn hình và tiêu chí hoàn thành |
| --- | --- | --- |
| FND-06 — Đăng nhập và phiên | `POST /api/auth/login`; `POST /api/auth/refresh`; `GET /api/auth/me`; `POST /api/auth/logout` | Login lưu access token, load `current_user` gồm `roles` và `permissions`, refresh một lần khi 401, giữ `redirect` sau login; không giữ token khi logout/hết phiên. |
| FND-07 — Tài khoản | `users.view/manage`; `GET /api/auth/users`, `/users/all`, `/users/{id}`; `POST /users`; `PUT, DELETE /users/{id}` | Bảng/drawer chi tiết, tạo/sửa/deactivate. Không cho UI vượt các ràng buộc backend như tự vô hiệu hóa hoặc làm mất administrator cuối cùng. |
| FND-08 — Vai trò | `roles.manage`; `GET /api/auth/roles`, `/roles/all`, `/roles/{id}`; `POST /roles`; `PUT, DELETE /roles/{id}` | Quản lý role và danh sách permission; role hệ thống là read-only nếu backend trả ràng buộc. |
| FND-09 — Gán role và quyền | `PUT /api/auth/users/{id}/roles`, `/users/{id}/permissions`, `/roles/{id}/permissions`; `permissions.view/manage` với CRUD `/api/auth/permissions` | Đồng bộ mảng ID qua dialog có nhóm quyền; refresh user hiện tại khi quyền của chính người dùng thay đổi; giữ backend là nguồn quyết định quyền thực tế. |

## 4. Dashboard và danh mục

### 4.1. Dashboard

| Task | Permission/API | Màn hình và tiêu chí hoàn thành |
| --- | --- | --- |
| FND-10 — Tổng quan điều độ | Permission nguồn `.view`; `GET /api/other/dashboard` | Filter kỳ; KPI doanh thu/thu/chi/lợi nhuận/công nợ; route strip chuyến sắp chạy/chưa phân công; cảnh báo xe và bằng lái. Widget thiếu permission bị ẩn, không hiển thị số 0 giả; drill-down mở đúng route/filter. |

### 4.2. Danh mục

Mỗi danh mục dùng list + form/detail, confirmation trước deactivate và collection danh mục để cấp select/search cho các form khác.

| Task | API mục tiêu | Màn hình và tiêu chí hoàn thành |
| --- | --- | --- |
| FND-11 — Khách hàng | `/api/master-data/customers` | Form `individual/company`, CCCD hoặc mã số thuế, liên hệ và công nợ đầu kỳ; detail link yêu cầu, hợp đồng, công nợ. |
| FND-12 — Đối tác | `/api/master-data/partners` | Form loại đối tác, thông tin liên hệ/ngân hàng/công nợ; detail link xe, tài xế, chi phí và chi trả. |
| FND-13 — Loại xe và xe | `/api/master-data/vehicle-types`, `/vehicles` | Xe chọn loại xe, ownership, partner, ODO, tình trạng; đổi sang xe partner bắt buộc chọn đối tác và hiển thị ràng buộc bảo dưỡng/ngưng hoạt động từ backend. |
| FND-14 — Tài xế | `/api/master-data/drivers` | Hồ sơ, bằng lái, lương/phụ cấp, liên kết `user_name`, công ty/đối tác; cảnh báo bằng sắp hết hạn/hết hạn; link lịch, công và lương. |
| FND-15 — Tuyến và giá tuyến | `/api/master-data/routes`, `/route-rates`, `/route-rates/lookup` | Quản lý tuyến, ca, điểm đón/trả, giờ chuẩn; giá theo loại xe và hiệu lực; dùng lookup để gợi ý giá/lương khi lập chứng từ, còn server quyết định giá hiệu lực. |
| FND-16 — Loại chi phí | `/api/master-data/expense-types` | Danh sách/form code, tên, scope `vehicle/trip/general`; hiển thị lỗi server nếu đang được dùng. |

## 5. Bán hàng và hợp đồng

### 5.1. Rental — yêu cầu và báo giá

| Task | Permission/API | Màn hình và tiêu chí hoàn thành |
| --- | --- | --- |
| FND-20 — Yêu cầu thuê xe | `rental-requests.view/manage`; `GET, POST /api/rental/requests`; `GET, PATCH, DELETE /requests/{id}` | List theo trạng thái/dịch vụ/khách/nguồn/kỳ; form master-detail `items[]` với loại xe, số lượng, tuyến, ghi chú; giữ dữ liệu khi lỗi transaction. |
| FND-21 — Lifecycle yêu cầu | `POST /api/rental/requests/{id}/mark-quoted`, `/accept`, `/reject` | Detail có timeline, items và CTA báo giá; action chỉ hiện ở state hợp lệ, reject nhận note tùy chọn và `409` refresh detail. |
| FND-22 — Báo giá | `quotations.view/manage`; `GET, POST /api/rental/quotations`; `GET, PATCH, DELETE /quotations/{id}` | List/detail và form nhiều dòng; tạo độc lập hoặc từ request. `amount`, subtotal, discount và total chỉ đọc từ response; không tự tính hoặc gửi total. |
| FND-23 — Lifecycle báo giá | `POST /api/rental/quotations/{id}/send`, `/approve`, `/reject`, `/expire` | Trang in gọn, hạn hiệu lực, điều khoản, attachment và CTA tạo hợp đồng khi approved; action chịu state/permission. |

### 5.2. Contract — hợp đồng và lịch định kỳ

| Task | Permission/API | Màn hình và tiêu chí hoàn thành |
| --- | --- | --- |
| FND-24 — Hợp đồng | `contracts.view/manage`; `GET, POST /api/contract/contracts`; `GET, PATCH, DELETE /contracts/{id}`; `POST /contracts/from-quotation` | List và detail theo khách/loại/kỳ/trạng thái. Form item gồm tuyến, loại xe, dịch vụ, số lượng, đơn giá, lương lái, điểm đón/trả; total là response backend. |
| FND-25 — Lifecycle hợp đồng | `POST /api/contract/contracts/{id}/activate`, `/complete`, `/cancel` | Detail tabs tổng quan, items, lịch, lịch chuyến, phiếu thu và attachment; sửa chỉ khi draft, dialog giải thích điều kiện action, sau action refresh toàn bộ tabs. |
| FND-26 — Quy tắc lịch | `GET, POST /api/contract/contracts/{contract_id}/schedule-rules`; `GET, PATCH, DELETE /schedule-rules/{id}`; `PUT /schedule-rules/{id}/days` | Builder rule cho hợp đồng active; grid thứ/ngày với giờ đón-về/ca; xe/tài xế mặc định chỉ là gợi ý phân công. |
| FND-27 — Sinh lịch chuyến | `POST /api/contract/schedule-rules/{id}/generate-trip-schedules` | Dialog chọn khoảng ngày, hiển thị `created`, `skipped`, `conflicts` từ response và link sang lịch chuyến với filter phù hợp. |

## 6. Điều hành

### 6.1. Lịch, nguồn lực và phân công desktop

| Task | Permission/API | Màn hình và tiêu chí hoàn thành |
| --- | --- | --- |
| FND-30 — Lịch chuyến | `trip-schedules.view/manage`; `GET, POST /api/dispatch/trip-schedules`; `GET, PATCH, DELETE /trip-schedules/{id}`; `POST /trip-schedules/{id}/cancel` | Timeline/route strip theo ngày-tuần và bảng dự phòng; filter thời gian, trạng thái, hợp đồng, dịch vụ, tuyến, xe/tài xế; sửa/xóa theo lifecycle backend. |
| FND-31 — Availability | `trip-assignments.view`; `GET /api/dispatch/availability` | Panel trong detail schedule bắt buộc start/end/type xe, nhận optional ownership/partner/exclude schedule; render xe/tài xế khả dụng và lý do loại nếu API trả. |
| FND-32 — Phân công/thay thế | `trip-assignments.view/manage`; `GET /api/dispatch/trip-schedules/{id}/assignments`; `POST /assignments`, `/assignments/substitute`; `DELETE /api/dispatch/trip-assignments/{id}` | Chọn từ availability, partner tự phản ánh theo xe, lịch sử assignment trên timeline; replace bắt buộc lý do. Khi `409`, giữ lựa chọn và cho chạy lại availability; không có xóa history substitute. |
| FND-33 — Lệnh điều xe | `dispatch-orders.view/manage`; `GET /api/dispatch/dispatch-orders`; `GET /dispatch-orders/{id}`; `POST /trip-schedules/{id}/dispatch-order`; `POST /dispatch-orders/{id}/assign`, `/cancel` | List theo ngày, xe/tài xế/khách/tuyến và detail in gọn. Chỉ tạo từ schedule assigned có assignment hiện hành; một schedule chỉ có một lệnh. |
| FND-34 — Bắt đầu/hoàn thành chuyến | `POST /api/dispatch/dispatch-orders/{id}/start`, `/complete` | Form thực tế cho giờ, ODO, km, giờ chờ, doanh thu/chi phí và note; client kiểm tra thứ tự thời gian/ODO, backend xác nhận cuối; complete refresh lệnh, schedule, ODO và link công. |

### 6.2. Mobile tài xế — phụ thuộc API

| Task | Phụ thuộc | Màn hình và tiêu chí hoàn thành |
| --- | --- | --- |
| FND-35 — Lệnh của tôi | Backend phải chốt endpoint ownership trả riêng các dispatch order thuộc tài xế hiện tại, cùng rule permission/ownership | Chỉ khi hợp đồng API tồn tại mới triển khai list và detail mobile: giờ, khách, điểm đón/trả, xe, liên hệ và CTA thao tác một tay. Không dùng list lệnh chung rồi lọc theo driver tại client. |
| FND-36 — Cập nhật tiến độ | Cùng ownership contract; dùng action start/complete ở FND-34 khi backend cho phép | Chỉ show Start/Complete đúng state, form ODO/thời gian/note/chứng từ gọn; `403/409` khóa action, refresh state, không lộ lệnh khác. |

## 7. Tài chính, chấm công và lương

### 7.1. Chứng từ và công nợ

| Task | Permission/API | Màn hình và tiêu chí hoàn thành |
| --- | --- | --- |
| FND-40 — Phiếu thu | `receipts.view/manage`; `/api/finance/receipts` CRUD; `POST /receipts/{id}/lock` | List/form/detail/in phiếu/attachment. Sau chọn contract, hiển thị contract total, đã thu và còn phải thu từ detail response; locked là read-only. |
| FND-41 — Chi phí | `expenses.view/manage`; `/api/finance/expenses` CRUD; `POST /expenses/{id}/lock` | Form đổi FK theo scope `vehicle/trip/general`, reset field không còn hợp lệ; attachment hóa đơn; locked là read-only. |
| FND-42 — Chi trả đối tác | `partner-payments.view/manage`; `/api/finance/partner-payments` CRUD; `POST /partner-payments/{id}/lock` | Form đối tác/lệnh/tiền/thời điểm/phương thức/diễn giải, in phiếu chi; giải thích `409` khi partner không khớp assignment. |
| FND-43 — Công nợ nhanh | Permission nguồn; `GET /api/other/reports/customer-debts`, `/partner-debts` | Tabs công nợ khách/đối tác, số đầu kỳ/phát sinh/đã thu-chi/cuối kỳ, drill-down chứng từ và export theo filter. |

### 7.2. Tạm ứng, công và payroll

| Task | Permission/API | Màn hình và tiêu chí hoàn thành |
| --- | --- | --- |
| FND-44 — Tạm ứng | `driver-advances.view/manage`; `/api/driver-payroll/advances` CRUD; `POST /advances/{id}/confirm` | List/form theo tài xế/kỳ/state; pending được sửa theo API, confirmed/payroll_locked chỉ đọc và có link payroll nếu có. |
| FND-45 — Chấm công chuyến | `driver-attendances.view/manage`; `GET /api/driver-payroll/attendances`, `/attendances/{id}`; `POST /dispatch-orders/{id}/attendance`; `PATCH /attendances/{id}`; `POST /attendances/{id}/confirm` | Tạo/xem công từ lệnh completed; detail thể hiện công thức server tính; không tự tạo trước completion và không sửa locked. |
| FND-46 — Kỳ lương | `payrolls.view/manage`; `GET, POST /api/driver-payroll/payrolls`; `GET, PATCH /payrolls/{id}` | List theo tháng/năm/status; tạo kỳ, detail tổng gross/net và items; `409` tháng/năm đã tồn tại hiển thị rõ. |
| FND-47 — Tính/duyệt/chốt lương | `POST /api/driver-payroll/payrolls/{id}/calculate`, `/approve`, `/mark-paid`, `/lock`; `PATCH /payrolls/{id}/items/{item_id}` | State header, bảng từng tài xế, expand nguồn công, điều chỉnh allowance/deduction/note; totals luôn từ response. Lock nêu bất biến và chuyển toàn trang read-only. |

## 8. Báo cáo, attachment và export

| Task | Permission/API | Màn hình và tiêu chí hoàn thành |
| --- | --- | --- |
| FND-50 — Tệp đính kèm | `attachments.view/manage`; `GET, POST /api/other/attachments`; `GET /attachments/{id}/download`; `DELETE /attachments/{id}` | Upload `multipart/form-data` với `file`, `attachable_type`, `attachable_id`; không gửi metadata file do server sinh; xóa chỉ khi parent cho phép. |
| FND-51 — Báo cáo xe chạy tuyến | Permission nguồn; `GET /api/other/reports/fixed-trips` | Filter kỳ/tuyến/khách/xe/tài xế; bảng ngày, ca, lệnh, ODO, km, doanh thu, trạng thái và drill-down lệnh. |
| FND-52 — Báo cáo xe du lịch/công tác | Permission nguồn; `GET /api/other/reports/tourism-trips` | Filter kỳ/khách/xe/đối tác/tài xế; hành trình, doanh thu, chi phí và trạng thái. |
| FND-53 — Thu chi, chi phí và công nợ | Permission nguồn; `GET /api/other/reports/cashflow`, `/expenses`, `/customer-debts`, `/partner-debts` | Tab/bảng chứng từ và đối chiếu; filter resource liên quan, link detail và export. |
| FND-54 — Báo cáo lương | Permission nguồn; `GET /api/other/reports/driver-payroll` | Filter kỳ/tài xế/status, gross/net/tạm ứng/khấu trừ, expand nguồn công và drill-down payroll. |
| FND-55 — Lợi nhuận | Permission nguồn; `GET /api/other/reports/profit-loss` | Chart/bảng doanh thu, chi phí, lợi nhuận theo tháng; luôn hiển thị `basis` backend trả về (`accrual` hoặc `cash`). |

Mọi report dùng cùng filter bar, skeleton chart, empty state “Không có dữ liệu trong kỳ”, retry và export lại đúng URL/filter với `format=csv`. Không suy ra tổng từ dữ liệu đang hiển thị.

## 9. Ma trận permission và lifecycle

- Không hard-code role để cấp quyền. Role chỉ cung cấp ngữ cảnh; luôn dùng danh sách `permissions` từ `/api/auth/me` và xử lý `403` của backend.
- Kinh doanh tập trung customers, requests, quotations, contracts; điều hành tập trung vehicles, drivers, routes, schedules, assignments, orders; kế toán tập trung tài chính/lương; quản lý xem dashboard/report theo permission nguồn.
- Action chỉ xuất hiện khi state API cho phép. Các lifecycle cần phản ánh đúng `backend_task.md`: request, quotation, contract, trip schedule, dispatch order, advance/attendance và payroll.
- State `locked` và `payroll_locked` là bất biến: không có UI sửa, deactivate hoặc đảo ngược. Nếu dữ liệu cũ dẫn tới `409`, báo rõ và refetch thay vì tự ghi đè.

## 10. Kiểm thử chấp nhận

1. Đăng nhập, refresh token, hết phiên và route guard hoạt động; menu/route/action ẩn hoặc trả 403 theo permission.
2. Với collection nghiệp vụ, xác nhận filter/sort/pagination xảy ra tại client và không phát `page`, `per_page`, `total`, `last_page` tới API.
3. Kiểm tra `401/403/404/409/422`: refresh/logout, trang hoặc toast phù hợp, giữ form ở 409/422 và map lỗi field/dòng chính xác.
4. Thử lifecycle sai: duyệt báo giá draft, hoàn thành lệnh chưa start, sửa chứng từ/payroll locked. CTA không xuất hiện khi biết state; lỗi backend vẫn được trình bày rõ.
5. Thử conflict xe/tài xế trên phân công: giữ lựa chọn, hiển thị `409`, cho refresh availability và không ghi đè assignment.
6. Chạy hành trình: khách hàng → request nhiều dòng → quotation → contract → rule/sinh lịch → availability/phân công → lệnh/start/complete → attendance/payroll → thu/chi → report/export. Sau mỗi mutation, mọi query liên quan được refetch/invalidate.
7. Khi backend đã chốt ownership API, kiểm tra mobile tài xế chỉ thấy lệnh được phép và có thể start/complete/upload chứng từ trên màn hẹp.
