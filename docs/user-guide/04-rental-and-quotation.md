# 04. Thuê xe và báo giá

## Mục đích

Quản lý một nhu cầu thuê xe từ lúc tiếp nhận đến khi khách phản hồi báo giá. Luồng này thường do nhân viên kinh doanh thực hiện.

## Ai có thể sử dụng

- Xem yêu cầu thuê xe: cần quyền xem yêu cầu thuê xe.
- Tạo, sửa, ngừng hoạt động yêu cầu và báo giá: cần quyền quản lý tương ứng.
- Tạo hợp đồng sau khi khách chấp nhận: cần quyền quản lý hợp đồng.
- Kiểm tra năng lực: cần quyền **Kiểm tra năng lực**.

## Yêu cầu thuê xe

Mở **Thuê xe > Yêu cầu thuê xe** để tra cứu, lọc theo ngày tiếp nhận và mở chi tiết từng yêu cầu.

### Tạo yêu cầu

1. Nhấn **Tạo yêu cầu**.
2. Chọn **Khách hàng**.
3. Nhập **Nguồn yêu cầu** nếu có, ví dụ Website, Zalo hoặc Hotline.
4. Chọn **Thời điểm tiếp nhận**, **Dịch vụ**, **Khởi hành** và **Kết thúc**.
5. Trong phần **Hành trình**, chọn một trong hai cách:
   - **Theo tuyến**: chọn một tuyến xe phù hợp với khách hàng.
   - **Tự nhập**: nhập **Điểm đón** và **Điểm trả**.
6. Thêm các **Hạng mục xe**: chọn loại xe, số lượng và ghi chú nếu cần.
7. Nhấn nút lưu ở cuối biểu mẫu.

Các điều kiện quan trọng:

- Khởi hành phải ở tương lai; kết thúc phải sau khởi hành.
- Thời điểm tiếp nhận không được ở tương lai hoặc sau thời điểm khởi hành.
- Nếu tự nhập hành trình, điểm đón và điểm trả là bắt buộc và phải khác nhau.
- Nếu dùng tuyến, mọi hạng mục xe phải dùng cùng một tuyến. Nếu không dùng tuyến thì mọi hạng mục đều không chọn tuyến.
- Khách hàng, loại xe và tuyến phải đang hoạt động.

### Sửa hoặc ngừng hoạt động yêu cầu

Chỉ yêu cầu **Mới** mới có thể sửa hoặc ngừng hoạt động. Từ danh sách, mở thao tác của dòng yêu cầu rồi chọn **Sửa** hoặc **Xóa**; ở đây thao tác xóa làm yêu cầu ngừng hoạt động, không dùng được cho nghiệp vụ mới.

### Trạng thái yêu cầu thuê xe

| Trạng thái | Ý nghĩa |
|---|---|
| **Mới** | Vừa tiếp nhận; còn có thể sửa hoặc ngừng hoạt động. |
| **Đã báo giá** | Đã có báo giá được gửi cho khách. |
| **Đã chấp nhận** | Khách đã chấp nhận một báo giá liên quan. |
| **Từ chối** | Yêu cầu hoặc phản hồi liên quan bị từ chối. |
| **Đã chuyển hợp đồng** | Đã chuyển sang hợp đồng. |

## Kiểm tra năng lực trước khi báo giá

Mở chi tiết yêu cầu. Khu vực **Năng lực phục vụ** kiểm tra số xe và tài xế khả dụng theo thời gian khởi hành/kết thúc cùng các hạng mục xe của yêu cầu.

- Kết quả cho biết số xe, số tài xế và danh sách ứng viên theo từng loại xe.
- Chỉ nhấn **Tạo báo giá** khi kết quả cho biết đáp ứng đủ năng lực.
- Báo giá nháp không giữ trước xe hoặc tài xế. Năng lực được kiểm tra lại khi lưu báo giá và khi kích hoạt hợp đồng.

## Báo giá

Mở **Thuê xe > Báo giá** để xem danh sách báo giá. Báo giá mới phải được tạo từ trang chi tiết của một yêu cầu thuê xe hợp lệ.

### Lập báo giá từ yêu cầu

1. Mở chi tiết yêu cầu có đủ năng lực.
2. Nhấn **Tạo báo giá**.
3. Kiểm tra khách hàng, yêu cầu và các hạng mục được điền sẵn.
4. Nhập **Đơn giá** cho từng hạng mục; có thể bổ sung nội dung mô tả.
5. Nhập **Ngày báo giá**, **Hạn hiệu lực**, **Chiết khấu** và **Điều khoản thanh toán** nếu cần.
6. Lưu báo giá nháp.

Hạng mục báo giá phải giữ đúng loại xe, tuyến và số lượng của yêu cầu thuê xe. Hạn hiệu lực không được trước ngày báo giá; chiết khấu không được lớn hơn tạm tính.

### Gửi hoặc ghi nhận phản hồi

Mở chi tiết một báo giá để thực hiện hành động theo trạng thái:

- Với báo giá **Dự thảo** còn hiệu lực và khách có email, nhấn **Gửi email**. Hệ thống tạo liên kết phản hồi có hiệu lực đến hết hạn báo giá và chuyển báo giá thành **Đã gửi**.
- Với báo giá **Đã gửi**, nhấn **Ghi nhận cuộc gọi** để ghi nhận khách **đồng ý** hoặc **từ chối**, kèm ghi chú cuộc gọi nếu cần.
- Với báo giá nháp của khách chưa có email, cũng có thể dùng **Ghi nhận cuộc gọi**.
- Nhấn **Hết hạn** khi cần đánh dấu sớm một báo giá đã gửi là hết hạn.

Người nhận email mở liên kết phản hồi để xem chi tiết, nhập ghi chú khi từ chối (tùy chọn) rồi chọn **Chấp nhận báo giá** hoặc **Từ chối**. Liên kết chỉ dùng một lần và không dùng được khi hết hạn hoặc báo giá đã không còn ở trạng thái đã gửi.

### Trạng thái báo giá

| Trạng thái | Ý nghĩa và thao tác |
|---|---|
| **Dự thảo** | Có thể sửa, xóa hoặc gửi/ghi nhận qua điện thoại khi đủ điều kiện. |
| **Đã gửi** | Đang chờ phản hồi; có thể ghi nhận cuộc gọi hoặc đánh dấu hết hạn. |
| **Đã duyệt** | Khách đã chấp nhận; có thể **Tạo hợp đồng** nếu có quyền. |
| **Từ chối** | Khách không chấp nhận. |
| **Hết hạn** | Không tiếp tục xử lý phản hồi. |
| **Đã thay thế** | Có báo giá khác cho cùng yêu cầu đã được chấp nhận hoặc yêu cầu bị từ chối. |

Khi một báo giá được chấp nhận, yêu cầu thuê xe chuyển thành **Đã chấp nhận** và các báo giá nháp/đã gửi khác của cùng yêu cầu chuyển thành **Đã thay thế**.

## Lưu ý quan trọng

- Chỉ báo giá **Dự thảo** mới sửa hoặc xóa được.
- Báo giá chỉ lập được khi yêu cầu còn hoạt động, đúng khách hàng, đang ở trạng thái **Mới** hoặc **Đã báo giá**, và có đủ năng lực xe/tài xế tại thời điểm kiểm tra.
- Nếu khách không chấp nhận, vẫn giữ ghi chú phản hồi để tiện đối soát.
