# 03. Danh mục dùng chung

## Mục đích

Các danh mục là dữ liệu nền cho thuê xe, điều hành, tài chính và lương. Nên khai báo đầy đủ trước khi bắt đầu nghiệp vụ mới.

## Ai có thể sử dụng

Tài khoản cần quyền xem danh mục tương ứng để mở màn hình, và quyền quản lý để thêm/sửa/ngừng hoạt động. Theo cấu hình mặc định, kinh doanh quản lý khách hàng; điều hành quản lý xe, tài xế, tuyến và bảng giá; quản trị viên có toàn quyền.

## Khách hàng

Mở **Quản lý danh mục > Khách hàng**. Màn hình cho phép tra cứu, thêm, sửa và ngừng hoạt động khách hàng.

### Thêm khách hàng

1. Nhấn **Thêm khách hàng**.
2. Chọn **Loại khách hàng**: **Cá nhân** hoặc **Công ty**.
3. Nhập **Tên khách hàng** và các thông tin liên hệ cần thiết.
4. Với khách hàng cá nhân, nhập **Căn cước công dân**. Với công ty, nhập **Mã số thuế**.
5. Nếu cần theo dõi công nợ có sẵn, nhập **Số dư đầu kỳ**.
6. Nhấn **Thêm mới**.

Tên khách hàng là bắt buộc. Email, nếu nhập, phải hợp lệ. Số dư đầu kỳ phải là số có tối đa hai chữ số thập phân.

## Đối tác

Mở **Quản lý danh mục > Đối tác**. Dùng để quản lý công ty vận tải, chủ xe, gara, nhà cung cấp nhiên liệu và đối tác khác.

Khi thêm/cập nhật, chọn loại đối tác, nhập tên, liên hệ, thông tin ngân hàng và **Công nợ đầu kỳ** nếu cần. Tên đối tác là bắt buộc; email phải hợp lệ nếu có nhập.

## Loại xe và xe

### Phân loại xe

Mở **Quản lý danh mục > Phân loại xe**.

- Nhập **Mã loại xe**, **Tên loại xe**, **Số chỗ** và **Tỷ lệ hoa hồng tài xế tour**.
- Số chỗ phải là số nguyên từ 1 trở lên.
- Tỷ lệ hoa hồng nằm trong khoảng 0–100%.

Loại xe được dùng khi lập yêu cầu thuê xe, bảng giá, hợp đồng và phân công xe.

### Danh sách xe

Mở **Quản lý danh mục > Danh sách xe**.

Khai báo **Biển số xe**, **Loại xe**, **Loại sở hữu**, thương hiệu/mẫu xe, năm sản xuất, ODO hiện tại, trạng thái xe và ghi chú. Nếu loại sở hữu là **Đối tác**, phải chọn đối tác; nếu là **Công ty**, hệ thống không gắn đối tác cho xe.

Chỉ xe đang hoạt động và ở trạng thái sẵn sàng mới có thể được chọn khi phân công. Bảo đảm ODO hiện tại chính xác vì ODO kết thúc của chuyến sẽ được đối chiếu với số này.

## Tài xế

Mở **Quản lý danh mục > Tài xế**.

Nhập họ tên, số điện thoại, CCCD (nếu có), **Số bằng lái**, **Hạng bằng lái**, ngày cấp/hết hạn, lương cơ bản, phụ cấp trách nhiệm và ngày làm việc. Nếu loại tài xế là đối tác, phải chọn đối tác; tài xế công ty không gắn đối tác.

Ngày hết hạn bằng lái không được trước ngày cấp. Ngày nghỉ việc không được trước ngày vào làm. Tài xế chỉ được phân công khi còn hoạt động, còn hiệu lực trong thời gian chuyến và không trùng chuyến khác.

## Tuyến xe

Mở **Quản lý danh mục > Tuyến đường**.

1. Nhấn **Thêm tuyến xe**.
2. Có thể chọn **Khách hàng** để tạo tuyến riêng cho khách đó, hoặc để trống để dùng chung.
3. Nhập **Tên tuyến**, **Điểm đón**, **Điểm trả**.
4. Có thể nhập **Tên ca**, giờ đón/trả mặc định và khoảng cách ước tính.
5. Lưu thay đổi.

Tên tuyến, điểm đón và điểm trả là bắt buộc. Giờ mặc định dùng định dạng `HH:mm`.

## Bảng giá tuyến xe

Mở **Quản lý danh mục > Bảng giá tuyến xe**.

Chọn tuyến xe và loại xe, sau đó nhập **Giá khách hàng**, **Lương tài xế**, **Ngày bắt đầu hiệu lực** và tùy chọn **Ngày kết thúc hiệu lực**. Giá và lương không âm; ngày kết thúc không được trước ngày bắt đầu.

Bảng giá là dữ liệu tham chiếu. Người lập báo giá vẫn nhập đơn giá trên từng hạng mục báo giá.

## Loại chi phí

Mở **Quản lý danh mục > Loại chi phí**.

Khai báo **Mã loại chi phí**, **Tên loại chi phí** và phạm vi áp dụng:

- **Xe**: chi phí gắn với một xe.
- **Chuyến xe**: chi phí gắn với một lệnh điều xe.
- **Chung**: chi phí không gắn riêng xe hoặc chuyến.

Mã và tên là bắt buộc. Chọn đúng phạm vi để chứng từ chi phí được kiểm tra đúng khi lưu.

## Ngừng hoạt động và kích hoạt lại

Các danh mục có thao tác ngừng hoạt động thay vì xóa hoàn toàn trong quy trình thông thường. Bản ghi ngừng hoạt động không còn dùng được trong nghiệp vụ mới. Khi mở bản ghi ngừng hoạt động để sửa, hệ thống có thể hiển thị tùy chọn **Kích hoạt lại**; chỉ bật tùy chọn này khi dữ liệu đã sẵn sàng dùng trở lại.
