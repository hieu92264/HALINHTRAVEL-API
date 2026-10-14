# 02. Vai trò và quyền hạn

## Nguyên tắc phân quyền

Hệ thống kiểm tra quyền ở cả giao diện và khi thực hiện thao tác. Quyền **xem** cho phép mở danh sách/chi tiết; quyền **quản lý** cho phép tạo, sửa, chuyển trạng thái hoặc ngừng hoạt động theo quy tắc của từng nghiệp vụ.

Quản trị viên có thể điều chỉnh quyền của tài khoản và vai trò tùy chỉnh. Bảng dưới đây mô tả cấu hình vai trò hệ thống mặc định; quyền thực tế của một tài khoản có thể khác nếu được cấp bổ sung hoặc thu hồi.

## Ma trận vai trò mặc định

| Vai trò | Phạm vi công việc mặc định |
|---|---|
| **Quản trị viên** | Toàn quyền trên toàn bộ chức năng và quản lý tổ chức. |
| **Ban giám đốc** | Xem dữ liệu nghiệp vụ; quản lý báo giá, hợp đồng, chứng từ tài chính, tạm ứng và kỳ lương. |
| **Nhân viên kinh doanh** | Quản lý khách hàng, yêu cầu thuê xe, báo giá, hợp đồng; xem kiểm tra năng lực. |
| **Nhân viên điều hành** | Quản lý xe, tài xế, tuyến, bảng giá tuyến; quản lý lịch chuyến, phân công, lệnh điều xe và kiểm tra năng lực. |
| **Kế toán** | Quản lý thu chi, thanh toán đối tác, công chuyến, tạm ứng và lương; xem khách hàng, đối tác, hợp đồng. |
| **Tài xế** | Chỉ xem và cập nhật lệnh điều xe thuộc chính tài xế đó. |

## Quản lý tài khoản

### Ai sử dụng

Người có quyền xem hoặc quản lý tài khoản. Tạo/sửa, ngừng hoạt động, gán vai trò và gán quyền trực tiếp yêu cầu quyền quản lý tài khoản.

### Thêm tài khoản

1. Mở **Quản lý tổ chức > Tài khoản**.
2. Nhấn **Thêm tài khoản**.
3. Nhập **Tên đăng nhập**, **Email** và **Mật khẩu**.
4. Nhấn xác nhận để lưu.
5. Mở chi tiết tài khoản vừa tạo để gán vai trò hoặc quyền trực tiếp.

Tên đăng nhập và email phải là duy nhất; mật khẩu có tối thiểu 8 ký tự.

### Gán vai trò và quyền trực tiếp

1. Tại danh sách tài khoản, mở **Xem chi tiết** một tài khoản.
2. Trong phần vai trò, chọn hoặc bỏ chọn vai trò cần áp dụng.
3. Trong phần quyền, tìm theo mã quyền rồi chọn các quyền bổ sung/thu hồi.
4. Lưu thay đổi ở từng phần.

Không thể tự gỡ vai trò quản trị viên của chính mình. Hệ thống cũng không cho ngừng hoạt động quản trị viên đang hoạt động cuối cùng.

### Ngừng hoạt động tài khoản

Mở chi tiết tài khoản và chọn thao tác ngừng hoạt động. Tài khoản ngừng hoạt động không thể tiếp tục sử dụng. Không thể ngừng hoạt động tài khoản đang đăng nhập của chính mình.

## Vai trò và quyền tùy chỉnh

### Vai trò

Tại **Quản lý tổ chức > Vai trò**, có thể tạo vai trò tùy chỉnh, đặt tên theo dạng mã chữ thường (ví dụ `operations-lead` hoặc `operations.lead`), rồi mở chi tiết vai trò để chọn quyền. Tên chỉ dùng chữ thường, số, dấu gạch nối và dấu chấm; phải bắt đầu bằng chữ cái.

Vai trò hệ thống chỉ đọc: có thể xem quyền nhưng không thể đổi tên hay xóa.

### Quyền

Tại **Quản lý tổ chức > Quyền**, danh sách quyền hiển thị mã quyền, loại và trạng thái. Có thể tạo quyền tùy chỉnh, nhưng mã quyền phải có ít nhất một dấu chấm, ví dụ `reports.export`. Quyền hệ thống không thể sửa hoặc xóa.

## Lưu ý cho quản trị viên

- Cấp quyền theo công việc, không cấp toàn quyền khi không cần thiết.
- Luôn kiểm tra một tài khoản tài xế đã được liên kết với hồ sơ tài xế trước khi dùng **Không gian tài xế**.
- Khi điều chỉnh vai trò, người dùng có thể cần đăng xuất và đăng nhập lại để tải quyền mới.
