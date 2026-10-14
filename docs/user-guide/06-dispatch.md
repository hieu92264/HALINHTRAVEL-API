# 06. Điều hành và lệnh điều xe

## Mục đích

Điều hành dùng màn hình này để tạo lịch chuyến, kiểm tra năng lực, phân công xe/tài xế, phát hành lệnh và xác nhận hoàn tất chuyến.

## Kiểm tra năng lực độc lập

Mở **Điều hành > Kiểm tra năng lực** khi cần đánh giá khả năng đáp ứng trước khi phân công.

1. Chọn thời gian bắt đầu và kết thúc.
2. Có thể giới hạn nguồn xe: **Tất cả nguồn xe**, **Xe công ty** hoặc **Xe đối tác**; khi chọn xe đối tác, chọn thêm đối tác.
3. Thêm từng **Loại xe yêu cầu** và số lượng.
4. Nhấn **Kiểm tra năng lực**.

Kết quả cho biết số xe/tài xế khả dụng, số lượng cần dùng, có đủ khả năng đáp ứng hay không, và danh sách xe/tài xế phù hợp. Mỗi loại xe chỉ nên thêm một lần. Kiểm tra này là ảnh chụp tại thời điểm thực hiện, không giữ trước nguồn lực.

## Lịch chuyến

Mở **Điều hành > Lịch chuyến**. Danh sách hiển thị mã lịch, thời gian, tuyến/hành trình, loại xe cần dùng, trạng thái, phân công và lệnh điều xe nếu có. Bấm vào một dòng để mở phần chi tiết điều hành.

### Tạo lịch chuyến thủ công

1. Nhấn **Tạo lịch chuyến**.
2. Chọn hợp đồng đang hiệu lực và hạng mục dịch vụ thuộc hợp đồng.
3. Nhập thời gian bắt đầu/kết thúc, điểm đón, điểm trả và ghi chú nếu cần.
4. Lưu lịch chuyến.

Lịch chỉ tạo được cho hợp đồng đang hiệu lực. Thời điểm kết thúc phải sau thời điểm bắt đầu. Ngoài cách tạo thủ công, hệ thống có thể sinh lịch từ hợp đồng theo chuyến hoặc từ quy tắc lịch cố định.

### Phân công xe và tài xế

1. Mở rộng một lịch ở trạng thái **Chờ phân công**.
2. Trong phần **Phân công**, nhấn **Kiểm tra năng lực**.
3. Chọn xe và tài xế từ danh sách phù hợp hiển thị trong kết quả.
4. Nhấn **Phân công**.

Hệ thống chỉ chấp nhận xe đang sẵn sàng, đúng loại xe yêu cầu, tài xế đang hoạt động và đủ hiệu lực bằng lái trong thời gian chuyến. Xe hoặc tài xế không được trùng với một lịch đang mở giao thời gian.

Sau khi phân công, lịch chuyển thành **Đã phân công**. Trước khi phát hành lệnh, có thể:

- Nhấn **Thay phân công**, nhập lý do rồi chọn xe/tài xế mới.
- Nhấn **Gỡ phân công** để đưa lịch về **Chờ phân công**.

Không thể thay hoặc gỡ phân công sau khi đã phát hành lệnh điều xe.

### Phát hành, hủy và ngừng lịch

- Khi lịch **Đã phân công**, nhấn **Phát hành lệnh** và xác nhận. Sau đó muốn thay phân công phải hủy lệnh trước.
- Với lịch **Chờ phân công** chưa có lệnh, có thể **Ngừng lịch** để không dùng tiếp.
- Nhấn **Hủy lịch** khi cần kết thúc lịch trước khi chạy. Không thể hủy lịch đã chạy, đã hoàn tất, đã hủy, hoặc còn lệnh điều xe đang hiệu lực.

### Trạng thái lịch chuyến

| Trạng thái | Ý nghĩa |
|---|---|
| **Chờ phân công** | Lịch đã lập, chưa có xe/tài xế hiện hành. |
| **Đã phân công** | Đã có xe và tài xế; có thể phát hành lệnh. |
| **Đang chạy** | Tài xế đã bắt đầu chuyến. |
| **Hoàn tất** | Điều hành đã xác nhận hoàn tất lệnh. |
| **Đã hủy** | Không tiếp tục phân công/chạy lịch. |

## Lệnh điều xe

Mở **Điều hành > Lệnh điều xe** để theo dõi và xử lý các lệnh đã phát hành. Bấm một dòng để xem hành trình, xe, tài xế, giờ thực tế, ODO và báo cáo vận hành.

### Bàn giao lệnh cho tài xế

Sau khi phát hành, lệnh có trạng thái **Đã phát hành**. Điều hành mở lệnh rồi nhấn **Xác nhận phân công** để chuyển sang **Đã phân công**. Tài xế chỉ có thể bắt đầu chuyến sau bước này.

### Xử lý báo cáo hoàn tất

Khi tài xế gửi báo cáo, lệnh chuyển sang **Chờ điều hành xác nhận**.

1. Mở lệnh cần xử lý.
2. Kiểm tra giờ/ODO bắt đầu-kết thúc, quãng đường, giờ chờ và ghi chú.
3. Nếu cần bổ sung, nhấn **Trả báo cáo**, nhập lý do. Lệnh quay về **Đang chạy** để tài xế gửi lại.
4. Nếu dữ liệu đúng, nhấn **Xác nhận hoàn tất**.
5. Nhập **Doanh thu khách hàng**, và nếu có thì **Chi phí xe đối tác**, **Chi phí tài xế ngoài**; xác nhận để chốt.

Khi xác nhận hoàn tất, lệnh và lịch chuyến chuyển thành **Hoàn tất**; ODO hiện tại của xe được cập nhật bằng ODO kết thúc. ODO kết thúc không được nhỏ hơn ODO bắt đầu hoặc ODO hiện tại của xe.

### Hủy lệnh

Lệnh ở trạng thái **Đã phát hành** hoặc **Đã phân công** có thể **Hủy lệnh**. Nhập lý do hủy. Sau khi hủy, lịch quay về trạng thái đã phân công để điều hành có thể xử lý lại, bao gồm thay phân công rồi phát hành lệnh mới nếu cần. Không thể hủy lệnh khi chuyến đã bắt đầu hoặc đã hoàn tất.

### Trạng thái lệnh điều xe

| Trạng thái | Người xử lý chính | Ý nghĩa |
|---|---|---|
| **Đã phát hành** | Điều hành | Lệnh vừa tạo từ lịch đã phân công; chờ xác nhận phân công. |
| **Đã phân công** | Tài xế | Có thể bắt đầu chuyến. |
| **Đang chạy** | Tài xế | Đã khai báo bắt đầu; chờ báo cáo hoàn tất. |
| **Chờ điều hành xác nhận** | Điều hành | Tài xế đã báo cáo, cần kiểm tra và chốt số liệu. |
| **Hoàn tất** | Điều hành | Chuyến và số liệu đã được xác nhận. |
| **Đã hủy** | Điều hành | Lệnh không còn hiệu lực. |

## Lưu ý điều hành

- Luôn bấm kiểm tra năng lực ngay trước khi phân công, đặc biệt khi lịch vừa được cập nhật.
- Không phát hành lệnh nếu chưa kiểm tra đúng xe, tài xế và giờ điều hành.
- Dùng **Trả báo cáo** thay vì xác nhận khi ODO, thời gian hoặc số liệu tài chính chưa rõ ràng.
