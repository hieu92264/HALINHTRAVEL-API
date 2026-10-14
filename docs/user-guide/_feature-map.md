# Feature map nội bộ

Tệp này là bản đồ bảo trì cho tài liệu người dùng; các chi tiết kỹ thuật dưới đây không phải hướng dẫn sử dụng trực tiếp. Mọi mục được đối chiếu từ mã nguồn, không có xác minh bằng trình duyệt/runtime.

```yaml
feature: authentication-and-dashboard
title: Đăng nhập và Tổng quan
confidence: VERIFIED_SOURCE
frontend:
  routes: [/login, /]
  screens: [Đăng nhập hệ thống, Tổng quan]
  navigation: [Tổng quan]
  actions: [Đăng nhập, Chọn ngày điều hành, Làm mới]
  forms:
    login:
      fields: [Tên đăng nhập hoặc email, Mật khẩu, Ghi nhớ đăng nhập]
      validation: [Mật khẩu tối thiểu 8 ký tự, tài khoản hoặc email phải tồn tại]
authorization:
  roles: [mọi người dùng đã đăng nhập]
  permissions: []
backend:
  endpoints: [POST /auth/login, GET /auth/me, GET /dashboard/overview]
  validators: [app/Modules/Auth/Requests/LoginRequest.php]
  policies: [auth:api]
  states: []
  transitions: []
workflow_dependencies:
  before: [Tài khoản đang hoạt động]
  after: [Các menu theo quyền]
evidence:
  - path: D:/vue/halinhtravel-client/src/modules/auth/login/components/LoginForm.vue
    purpose: Form, nhãn và thông báo đăng nhập
  - path: D:/vue/halinhtravel-client/src/modules/dashboard/DashboardPage.vue
    purpose: Nội dung Tổng quan
  - path: app/Modules/Auth/Routes/index.php
    purpose: Tuyến xác thực
notes: [Chức năng Quên mật khẩu chưa có luồng thực thi được xác minh.]
---
feature: access-management
title: Tài khoản, vai trò và quyền
confidence: VERIFIED_SOURCE
frontend:
  routes: [/users, /users/:id, /roles, /roles/:id, /permissions]
  screens: [Tài khoản người dùng, Chi tiết tài khoản, Vai trò, Chi tiết vai trò, Quyền]
  navigation: [Quản lý tổ chức > Tài khoản, Quản lý tổ chức > Vai trò, Quản lý tổ chức > Quyền]
  actions: [Thêm tài khoản, Sửa, Ngừng hoạt động, Gán vai trò, Gán quyền, Thêm/Sửa/Xóa vai trò tùy chỉnh, Thêm/Sửa/Xóa quyền tùy chỉnh]
  forms:
    user:
      fields: [Tên đăng nhập, Email, Mật khẩu]
      validation: [Tên/email duy nhất, mật khẩu tối thiểu 8 ký tự]
authorization:
  roles: [admin]
  permissions: [users.view, users.manage, roles.manage, permissions.view, permissions.manage]
backend:
  endpoints: [GET/POST/PATCH/DELETE /auth/users, PUT /auth/users/{user}/roles, PUT /auth/users/{user}/permissions, GET/POST/PATCH/DELETE /auth/roles, GET/POST/PATCH/DELETE /auth/permissions]
  validators: [app/Modules/Auth/Requests/StoreUserRequest.php, app/Modules/Auth/Requests/UpdateUserRequest.php, app/Modules/Auth/Requests/StoreRoleRequest.php]
  policies: [permission middleware]
  states: [Tài khoản hoạt động/ngừng hoạt động, Vai trò hệ thống/tùy chỉnh, Quyền hệ thống/tùy chỉnh]
  transitions: [Ngừng hoạt động tài khoản]
workflow_dependencies:
  before: [Người thực hiện có quyền quản lý]
  after: [Quyền hiệu lực cho phiên đăng nhập mới]
evidence:
  - path: D:/vue/halinhtravel-client/src/modules/organization/user/UserDetailPage.vue
    purpose: Gán vai trò/quyền và trạng thái tài khoản
  - path: app/Modules/Auth/Services/AccessManagementService.php
    purpose: Ràng buộc quản trị viên cuối cùng và đối tượng hệ thống
  - path: app/Modules/Auth/Database/Seeds/AuthDatabaseSeeder.php
    purpose: Ma trận sáu vai trò mặc định
notes: []
---
feature: master-data
title: Danh mục dùng chung
confidence: VERIFIED_SOURCE
frontend:
  routes: [/customers, /partners, /vehicle-types, /vehicles, /drivers, /routes, /route-rates, /expense-types]
  screens: [Khách hàng, Đối tác, Phân loại xe, Danh sách xe, Tài xế, Tuyến xe, Bảng giá tuyến xe, Loại chi phí]
  navigation: [Quản lý danh mục]
  actions: [Xem, tìm kiếm, lọc, thêm, cập nhật, ngừng hoạt động, kích hoạt lại]
  forms:
    main_fields: [Khách hàng/đối tác, loại xe/xe/tài xế, tuyến, bảng giá, loại chi phí]
    validation: [Đối tượng liên kết phải đang hoạt động, điều kiện theo loại khách hàng/sở hữu/phạm vi]
authorization:
  roles: [admin, sales, dispatcher]
  permissions: [customers.view/manage, partners.view/manage, vehicle-types.view/manage, vehicles.view/manage, drivers.view/manage, routes.view/manage, route-rates.view/manage, expense-types.view/manage]
backend:
  endpoints: [GET/POST/PATCH/DELETE /master-data/customers, GET/POST/PATCH/DELETE /master-data/partners, GET/POST/PATCH/DELETE /master-data/vehicle-types, GET/POST/PATCH/DELETE /master-data/vehicles, GET/POST/PATCH/DELETE /master-data/drivers, GET/POST/PUT/DELETE /master-data/routes, GET/POST/PATCH/DELETE /master-data/route-rates, GET/POST/PATCH/DELETE /master-data/expense-types]
  validators: [app/Modules/MasterData/Requests]
  policies: [permission middleware]
  states: [is_active, vehicle_status]
  transitions: [Ngừng hoạt động, Kích hoạt lại qua cập nhật]
workflow_dependencies:
  before: []
  after: [Thuê xe, hợp đồng, điều hành, tài chính và lương]
evidence:
  - path: D:/vue/halinhtravel-client/src/modules/master-data
    purpose: Màn hình, biểu mẫu và kiểm tra giao diện
  - path: app/Modules/MasterData/Routes/index.php
    purpose: Quyền và tuyến API
  - path: app/Modules/MasterData/Requests
    purpose: Kiểm tra dữ liệu máy chủ
notes: [Danh mục được ngừng hoạt động thay vì xóa cứng trong luồng UI.]
---
feature: rental-requests-and-quotations
title: Yêu cầu thuê xe và Báo giá
confidence: VERIFIED_SOURCE
frontend:
  routes: [/rental-requests, /rental-requests/create, /rental-requests/:id, /quotations, /quotations/:id, /quotation-response]
  screens: [Yêu cầu thuê xe, Chi tiết yêu cầu thuê xe, Tạo/Cập nhật yêu cầu, Báo giá, Chi tiết báo giá, Phản hồi báo giá]
  navigation: [Thuê xe > Yêu cầu thuê xe, Thuê xe > Báo giá]
  actions: [Tạo yêu cầu, Sửa/Xóa yêu cầu mới, Kiểm tra năng lực, Tạo báo giá, Gửi email, Ghi nhận cuộc gọi, Hết hạn, Phản hồi công khai]
  forms:
    rental_request:
      fields: [Khách hàng, nguồn, thời điểm tiếp nhận, dịch vụ, khởi hành/kết thúc, hành trình, hạng mục xe]
      validation: [Khởi hành tương lai, kết thúc sau khởi hành, hành trình/tuyến nhất quán]
    quotation:
      fields: [Ngày báo giá, hạn hiệu lực, chiết khấu, điều khoản, loại xe, số lượng, đơn giá]
      validation: [Hạn hiệu lực không trước ngày báo giá, chiết khấu không âm]
authorization:
  roles: [admin, director, sales]
  permissions: [rental-requests.view/manage, quotations.view/manage, rental-capacity.view]
backend:
  endpoints: [GET/POST/PATCH/DELETE /rental/requests, GET/POST/PATCH/DELETE /rental/quotations, POST /rental/quotations/{quotation}/send, POST /rental/quotations/{quotation}/expire, POST /rental/quotations/{quotation}/record-customer-response, GET/POST /public/quotation-responses/{token}]
  validators: [app/Modules/Rental/Requests/StoreRentalRequest.php, app/Modules/Rental/Requests/StoreQuotationRequest.php]
  policies: [permission middleware, public quotation-response throttle]
  states: ["request: new/quoted/accepted/rejected/converted", "quotation: draft/sent/approved/rejected/expired/superseded"]
  transitions: [draft→sent, sent/draft-without-email→approved|rejected, new→quoted, approved quote→accepted request]
workflow_dependencies:
  before: [Khách hàng, loại xe, tuyến đang hoạt động]
  after: [Hợp đồng theo chuyến]
evidence:
  - path: D:/vue/halinhtravel-client/src/modules/rental
    purpose: Luồng UI và nhãn trạng thái
  - path: app/Modules/Rental/Services/QuotationService.php
    purpose: Kiểm tra năng lực, phản hồi và chuyển trạng thái
notes: [Báo giá nháp không giữ xe hoặc tài xế.]
---
feature: contracts-and-recurring-schedules
title: Hợp đồng và Lịch cố định
confidence: VERIFIED_SOURCE
frontend:
  routes: [/contracts, /contracts/create, /contracts/from-quotation/:quotationId, /contracts/:id]
  screens: [Hợp đồng, Tạo/Cập nhật hợp đồng, Chi tiết hợp đồng, Lịch cố định]
  navigation: [Thuê xe > Hợp đồng]
  actions: [Tạo nguyên tắc, Tạo từ báo giá, Kích hoạt, Hoàn thành, Hủy, Ngừng hoạt động, Tạo/sửa/ngừng quy tắc, Sinh lịch chuyến]
  forms:
    contract:
      fields: [Khách hàng, ngày ký, hiệu lực, đặt cọc, điều khoản, hạng mục]
      validation: [Ngày ký/hiệu lực hợp lệ, đặt cọc không quá tổng]
    schedule_rule:
      fields: [Hạng mục, tuyến, xe/tài xế mặc định, hiệu lực, thứ, giờ đón/trả, ca]
      validation: [Nằm trong hiệu lực hợp đồng, không trùng thứ/giờ đón]
authorization:
  roles: [admin, director, sales]
  permissions: [contracts.view, contracts.manage]
backend:
  endpoints: [GET/POST/PATCH/DELETE /contract/contracts, POST /contract/contracts/from-quotation, POST /contract/contracts/{contract}/activate, POST /contract/contracts/{contract}/complete, POST /contract/contracts/{contract}/cancel, POST/PATCH/DELETE schedule-rules, POST generate-trip-schedules]
  validators: [app/Modules/Contract/Requests]
  policies: [permission middleware]
  states: [draft, active, completed, cancelled]
  transitions: [draft→active, active→completed, draft|active→cancelled]
workflow_dependencies:
  before: [Khách hàng, báo giá được duyệt cho hợp đồng theo chuyến]
  after: [Lịch chuyến và điều hành]
evidence:
  - path: D:/vue/halinhtravel-client/src/modules/contract
    purpose: Biểu mẫu, trạng thái và lịch cố định
  - path: app/Modules/Contract/Services/ContractService.php
    purpose: Ràng buộc trạng thái/hợp đồng theo chuyến
  - path: app/Modules/Contract/Services/ContractScheduleRuleService.php
    purpose: Ràng buộc quy tắc và sinh lịch
notes: [Quy tắc không còn sửa được sau khi đã sinh lịch chuyến.]
---
feature: dispatch-and-driver-workspace
title: Điều hành, lệnh điều xe và Không gian tài xế
confidence: VERIFIED_SOURCE
frontend:
  routes: [/trip-schedules, /capacity, /dispatch-orders, /my-dispatch-orders, /my-dispatch-orders/:id]
  screens: [Lịch chuyến, Kiểm tra năng lực, Lệnh điều xe, Lệnh của tôi, Chi tiết lệnh]
  navigation: [Điều hành, Không gian tài xế]
  actions: [Tạo/Sửa/Hủy lịch, kiểm tra năng lực, phân công/thay/gỡ phân công, phát hành/xác nhận/hủy lệnh, bắt đầu, báo hoàn tất, trả/xác nhận báo cáo]
  forms:
    dispatch:
      fields: [Thời gian, hợp đồng/hạng mục, xe, tài xế, ODO, quãng đường, giờ chờ, doanh thu/chi phí]
      validation: [Kết thúc sau bắt đầu, ODO tăng dần, xe/tài xế không giao lịch]
authorization:
  roles: [admin, dispatcher, driver]
  permissions: [rental-capacity.view, trip-schedules.view/manage, trip-assignments.view/manage, dispatch-orders.view/manage, my-dispatch-orders.view/manage]
backend:
  endpoints: [POST /dispatch/availability, GET/POST/PATCH /dispatch/trip-schedules, POST assignments/substitute, POST orders/issue/assign/confirm-completion/return-completion/cancel, GET/POST /dispatch/my-orders]
  validators: [app/Modules/Dispatch/Requests]
  policies: [permission middleware, driver ownership]
  states: ["schedule: PLANNED/ASSIGNED/IN_PROGRESS/COMPLETED/CANCELLED", "order: ISSUED/ASSIGNED/IN_PROGRESS/PENDING_CONFIRMATION/COMPLETED/CANCELLED"]
  transitions: [planned schedule→assigned schedule, issued order→assigned→in_progress→pending_confirmation→completed]
workflow_dependencies:
  before: [Hợp đồng đang hiệu lực, xe/tài xế phù hợp]
  after: [ODO xe cập nhật, công chuyến/lương, báo cáo]
evidence:
  - path: D:/vue/halinhtravel-client/src/modules/dispatch
    purpose: Các màn hình điều hành và tài xế
  - path: app/Modules/Dispatch/Services/DispatchOrderService.php
    purpose: Luồng lệnh, ODO và sở hữu lệnh tài xế
notes: [Tài xế chỉ xem/cập nhật lệnh thuộc hồ sơ tài xế liên kết với tài khoản.]
---
feature: finance-documents-and-debt
title: Thu chi, chi trả đối tác và Công nợ
confidence: VERIFIED_SOURCE
frontend:
  routes: [/finance]
  screens: [Tài chính và công nợ, Phiếu thu, Chi phí, Chi trả đối tác, Công nợ nhanh]
  navigation: [Tài chính và nhân sự > Thu chi và công nợ]
  actions: [Tạo/Sửa/Khóa/Ngừng hoạt động chứng từ, lọc công nợ]
  forms:
    documents:
      fields: [Khách hàng/đối tác/loại chi phí, phạm vi, ngày, số tiền, phương thức, hợp đồng/lệnh/xe, diễn giải]
      validation: [Phiếu thu theo hợp đồng không vượt tổng hợp đồng, liên kết theo phạm vi/đối tác]
authorization:
  roles: [admin, director, accountant]
  permissions: [receipts.view/manage, expenses.view/manage, partner-payments.view/manage]
backend:
  endpoints: [GET/POST/PATCH/DELETE /finance/receipts, /finance/expenses, /finance/partner-payments; POST lock cho từng loại]
  validators: [app/Modules/Finance/Requests]
  policies: [permission middleware]
  states: [is_active, is_locked]
  transitions: [Khóa, Ngừng hoạt động]
workflow_dependencies:
  before: [Khách hàng/đối tác/loại chi phí và liên kết nghiệp vụ đang hoạt động]
  after: [Công nợ, báo cáo thu chi/lợi nhuận]
evidence:
  - path: D:/vue/halinhtravel-client/src/modules/finance/FinancePage.vue
    purpose: Thẻ và thao tác tài chính
  - path: app/Modules/Finance/Services/FinanceService.php
    purpose: Ràng buộc chứng từ và khóa
notes: [Chứng từ đã khóa không có luồng mở khóa được xác minh.]
---
feature: driver-payroll
title: Tạm ứng, Chấm công chuyến và Lương
confidence: PARTIAL
frontend:
  routes: [/driver-payroll, /driver-payroll/payrolls/:id]
  screens: [Chấm công và lương, Tạm ứng, Chấm công chuyến, Kỳ lương, Chi tiết kỳ lương]
  navigation: [Tài chính và nhân sự > Chấm công và lương]
  actions: [Tạo/Sửa/Xác nhận tạm ứng, điều chỉnh/xác nhận công, tạo/tính/duyệt/đánh dấu đã chi/chốt lương]
  forms:
    payroll:
      fields: [Tháng, năm, từ ngày, đến ngày, điều chỉnh phụ cấp/khấu trừ]
      validation: [Một kỳ cho mỗi tháng/năm, trạng thái theo quy trình]
authorization:
  roles: [admin, director, accountant]
  permissions: [driver-advances.view/manage, driver-attendances.view/manage, payrolls.view/manage]
backend:
  endpoints: [GET/POST/PATCH /driver-payroll/advances, POST confirm, GET/PATCH/POST confirm attendances, GET/POST/PATCH payrolls, POST calculate/approve/mark-paid/lock]
  validators: [app/Modules/DriverPayroll/Requests]
  policies: [permission middleware]
  states: ["advance/attendance: pending/confirmed/payroll_locked", "payroll: draft/calculated/approved/paid/locked"]
  transitions: [pending→confirmed→payroll_locked, draft→calculated→approved→paid→locked]
workflow_dependencies:
  before: [Tài xế công ty và dữ liệu công/tạm ứng đã xác nhận]
  after: [Báo cáo lương/lợi nhuận]
evidence:
  - path: D:/vue/halinhtravel-client/src/modules/finance/PayrollPage.vue
    purpose: Thao tác tạm ứng, xác nhận công và kỳ lương
  - path: app/Modules/DriverPayroll/Services/DriverPayrollService.php
    purpose: Tính/chốt lương và ràng buộc nguồn dữ liệu
notes: [API có tạo công chuyến từ lệnh hoàn tất nhưng frontend hiện không có thao tác tạo công; không hướng dẫn bước tạo công trong tài liệu người dùng.]
---
feature: reports
title: Báo cáo và xuất Excel
confidence: VERIFIED_SOURCE
frontend:
  routes: [/reports/fixed-trips, /reports/tourism-trips, /reports/finance, /reports/driver-payroll, /reports/profit-loss]
  screens: [Xe chạy tuyến, Xe du lịch/công tác, Thu chi chi phí công nợ, Báo cáo lương, Lợi nhuận]
  navigation: [Báo cáo]
  actions: [Chọn khoảng ngày, chọn cơ sở lợi nhuận, cập nhật, đặt lại, tìm kiếm/lọc, xuất Excel]
  forms:
    report:
      fields: [Từ ngày, đến ngày, cơ sở dồn tích/tiền mặt]
      validation: [Đến ngày không trước từ ngày]
authorization:
  roles: [admin, director, accountant]
  permissions: [dispatch-orders.view, receipts.view, expenses.view, partner-payments.view, driver-advances.view, payrolls.view]
backend:
  endpoints: [GET /other/reports/fixed-trips, tourism-trips, cashflow, expenses, customer-debts, partner-debts, driver-payroll, profit-loss]
  validators: [app/Modules/Other/Requests/ReportRequest.php]
  policies: [permission middleware]
  states: []
  transitions: []
workflow_dependencies:
  before: [Dữ liệu vận hành/tài chính/lương]
  after: []
evidence:
  - path: D:/vue/halinhtravel-client/src/modules/reports/ReportsPage.vue
    purpose: Bộ lọc, thẻ báo cáo và xuất Excel
  - path: app/Modules/Other/Services/ReportService.php
    purpose: Nội dung tổng hợp và cơ sở lợi nhuận
notes: [Báo cáo lợi nhuận yêu cầu nhiều quyền dữ liệu nguồn ở backend.]
```
