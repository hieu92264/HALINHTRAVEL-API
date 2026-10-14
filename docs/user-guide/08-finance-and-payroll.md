# 08. Tài chính và nhân sự

## Mục đích

Nhóm chức năng này phục vụ ghi nhận thu, chi, chi trả đối tác, công nợ, tạm ứng tài xế, công chuyến và quy trình lương.

## Thu chi và công nợ

Mở **Tài chính và nhân sự > Thu chi và công nợ**. Màn hình có bốn thẻ: **Phiếu thu**, **Chi phí**, **Chi trả đối tác** và **Công nợ nhanh**.

### Phiếu thu

Nhấn **Tạo phiếu thu**, sau đó chọn khách hàng và nhập:

- **Loại thu**: Đặt cọc, Thanh toán hợp đồng hoặc Khác.
- Ngày thu, số tiền, phương thức tiền mặt/chuyển khoản.
- Người nộp, mã hợp đồng và diễn giải nếu cần.

Nếu chọn **Đặt cọc** hoặc **Thanh toán hợp đồng**, phải nhập hợp đồng thuộc đúng khách hàng. Tổng các khoản thu đang hoạt động cho hợp đồng không được vượt giá trị hợp đồng.

### Chi phí

Nhấn **Tạo chi phí**, chọn loại chi phí rồi nhập phạm vi, ngày chi, số tiền, phương thức và diễn giải.

| Phạm vi | Thông tin bắt buộc |
|---|---|
| **Xe** | Mã xe |
| **Chuyến xe** | Mã lệnh điều xe |
| **Chi phí chung** | Không gắn riêng xe hoặc lệnh |

Có thể nhập số hóa đơn. Chọn đúng phạm vi vì hệ thống kiểm tra liên kết xe/lệnh tương ứng khi lưu.

### Chi trả đối tác

Nhấn **Tạo phiếu chi**, chọn đối tác, ngày chi, số tiền, phương thức, mã lệnh điều xe (nếu có) và diễn giải.

Nếu gắn với lệnh điều xe, đối tác phải khớp với đối tác trong phân công của lệnh đó.

### Khóa hoặc ngừng hoạt động chứng từ

Trên từng phiếu thu, chi phí hoặc phiếu chi đối tác, người có quyền quản lý có thể:

- **Sửa** khi chứng từ chưa khóa.
- **Khóa** để chốt chứng từ. Sau khi khóa, chứng từ chỉ xem, không thể sửa hay ngừng hoạt động.
- **Ngừng hoạt động** khi chứng từ chưa khóa. Chứng từ không còn xuất hiện trong nghiệp vụ đang hoạt động.

Kiểm tra kỹ số tiền, khách hàng/đối tác và liên kết hợp đồng/lệnh trước khi khóa.

### Công nợ nhanh

Chọn khoảng **Từ ngày** và **Đến ngày**, nhấn **Cập nhật**, rồi chọn thẻ **Khách hàng** hoặc **Đối tác**.

- Công nợ khách hiển thị số dư đầu kỳ, giá trị phát sinh/hợp đồng, đã thu và số dư cuối kỳ.
- Công nợ đối tác hiển thị số dư đầu kỳ, chi phí phát sinh, đã chi và số dư cuối kỳ.

Số liệu tại đây lấy từ dữ liệu đối soát, không suy ra từ riêng bảng chứng từ đang mở.

## Chấm công và lương

Mở **Tài chính và nhân sự > Chấm công và lương**. Có ba thẻ **Tạm ứng**, **Chấm công chuyến** và **Kỳ lương**.

### Tạm ứng tài xế

1. Tại thẻ **Tạm ứng**, nhấn **Tạo tạm ứng**.
2. Chọn tài xế, ngày tạm ứng, số tiền và diễn giải.
3. Lưu tạm ứng.
4. Khi dữ liệu đúng, chọn **Xác nhận**.

Tạm ứng mới ở trạng thái **Chờ xác nhận** và có thể sửa/ngừng hoạt động. Sau khi xác nhận, tạm ứng trở thành nguồn tính lương và không còn sửa được cho tới khi được xử lý trong kỳ lương.

### Chấm công chuyến

Thẻ **Chấm công chuyến** hiển thị công của các lệnh đã hoàn tất để kế toán xác nhận. Công chờ xác nhận có thể được điều chỉnh số đơn vị công hoặc đơn giá, sau đó nhấn **Xác nhận** để dùng làm nguồn tính lương.

Trong giao diện hiện tại không có nút tạo công chuyến. Việc đưa công chuyến mới vào danh sách chưa có thao tác người dùng được xác minh trong phiên bản này; nếu danh sách chưa có công cho lệnh đã hoàn tất, liên hệ quản trị viên hoặc bộ phận phụ trách triển khai thay vì tự tạo dữ liệu ngoài quy trình.

### Tạo và tính kỳ lương

1. Tại thẻ **Kỳ lương**, nhấn **Tạo kỳ lương**.
2. Nhập tháng, năm, từ ngày và đến ngày.
3. Lưu kỳ lương. Mỗi tháng/năm chỉ có một kỳ lương.
4. Mở kỳ lương rồi nhấn **Tính lương**.

Hệ thống lấy công chuyến và tạm ứng **đã xác nhận** trong kỳ, áp dụng cho tài xế công ty đang hoạt động. Kết quả có lương cơ bản, phụ cấp trách nhiệm, công chuyến, hoa hồng tour, tạm ứng, khấu trừ và thực nhận.

### Điều chỉnh và chốt lương

Khi kỳ lương ở trạng thái **Đã tính**, mở dòng tài xế và chọn **Điều chỉnh** để sửa phụ cấp ăn, phụ cấp khác, khấu trừ và ghi chú. Hệ thống tự tính lại tổng và thực nhận.

Quy trình trạng thái:

```text
Nháp → Đã tính → Đã duyệt → Đã chi → Đã chốt
```

1. **Tính lương** từ kỳ nháp hoặc kỳ đã tính.
2. Kiểm tra từng dòng và thực hiện điều chỉnh khi cần.
3. Nhấn **Duyệt**.
4. Sau khi chi thực tế, nhấn **Đánh dấu đã chi**.
5. Nhấn **Chốt lương** để kết thúc kỳ.

Chốt lương không thể đảo ngược. Khi chốt, các công chuyến và tạm ứng nguồn của kỳ trở thành dữ liệu khóa.

## Trạng thái nhân sự

| Đối tượng | Trạng thái | Ý nghĩa |
|---|---|---|
| Tạm ứng/công chuyến | **Chờ xác nhận** | Có thể điều chỉnh theo quyền. |
| Tạm ứng/công chuyến | **Đã xác nhận** | Sẵn sàng làm nguồn tính lương. |
| Tạm ứng/công chuyến | **Đã chốt lương** | Đã được chốt trong kỳ lương, không thay đổi. |
| Kỳ lương | **Nháp** | Mới tạo, chưa tính. |
| Kỳ lương | **Đã tính** | Có dòng lương, còn có thể điều chỉnh khoản cho phép. |
| Kỳ lương | **Đã duyệt** | Sẵn sàng chi trả. |
| Kỳ lương | **Đã chi** | Đã ghi nhận chi trả. |
| Kỳ lương | **Đã chốt** | Bất biến. |
