# 05. Hợp đồng và lịch cố định

## Mục đích

Quản lý hợp đồng nguyên tắc, hợp đồng theo chuyến tạo từ báo giá đã chấp nhận, và quy tắc sinh lịch cố định.

## Ai có thể sử dụng

Mở **Thuê xe > Hợp đồng** khi có quyền xem hợp đồng. Tạo, sửa, kích hoạt, hoàn thành, hủy, ngừng hoạt động hợp đồng và quản lý lịch cố định yêu cầu quyền quản lý hợp đồng.

## Hai loại hợp đồng

| Loại | Cách tạo | Đặc điểm |
|---|---|---|
| **Hợp đồng nguyên tắc** | Tạo trực tiếp | Dùng cho thỏa thuận có hiệu lực theo kỳ; có thể tạo quy tắc lịch cố định. |
| **Hợp đồng theo chuyến** | Tạo từ báo giá đã duyệt | Kế thừa khách hàng, yêu cầu và hạng mục từ báo giá; dùng cho yêu cầu thuê xe cụ thể. |

## Tạo hợp đồng nguyên tắc

1. Mở **Thuê xe > Hợp đồng**.
2. Nhấn **Tạo hợp đồng**.
3. Chọn **Khách hàng**.
4. Nhập **Ngày ký**, **Hiệu lực từ**, **Hiệu lực đến**, tiền **Đặt cọc**, điều khoản thanh toán và điều khoản hợp đồng khi cần.
5. Thêm ít nhất một hạng mục: tuyến (nếu có), loại xe, loại dịch vụ, số lượng, đơn giá, lương tài xế, điểm đón/trả và ghi chú.
6. Lưu hợp đồng. Hợp đồng mới có trạng thái **Nháp**.

Ngày ký không được muộn hơn ngày hiệu lực; ngày kết thúc không được trước ngày bắt đầu. Tiền đặt cọc không được lớn hơn tổng giá trị của các hạng mục.

## Tạo hợp đồng từ báo giá

1. Mở chi tiết báo giá ở trạng thái **Đã duyệt**.
2. Nhấn **Tạo hợp đồng**.
3. Nhập ngày ký, khoảng hiệu lực, đặt cọc và điều khoản cần bổ sung.
4. Lưu hợp đồng theo chuyến ở trạng thái **Nháp**.

Báo giá phải được chấp nhận và gắn với yêu cầu thuê xe đã chấp nhận. Khoảng hiệu lực hợp đồng phải bao trùm toàn bộ thời gian của yêu cầu thuê xe. Hợp đồng theo chuyến được tạo từ báo giá không sửa trực tiếp được.

## Vòng đời hợp đồng

| Trạng thái | Ý nghĩa | Thao tác tiếp theo |
|---|---|---|
| **Nháp** | Hợp đồng đã lập nhưng chưa có hiệu lực | Sửa hợp đồng nguyên tắc, **Kích hoạt**, hủy hoặc ngừng hoạt động. |
| **Đang hiệu lực** | Có thể dùng để tạo lịch/chạy chuyến | Hoàn thành hoặc hủy nếu đáp ứng điều kiện. |
| **Hoàn thành** | Đã kết thúc thực hiện | Chỉ xem. |
| **Đã hủy** | Không tiếp tục thực hiện | Chỉ xem. |

### Kích hoạt

Từ danh sách hoặc chi tiết hợp đồng nháp, chọn **Kích hoạt**. Hợp đồng phải có hạng mục và khoảng hiệu lực hợp lệ. Với hợp đồng theo chuyến, hệ thống kiểm tra lại năng lực điều độ; khi đủ năng lực, hệ thống sinh lịch chuyến theo số lượng hạng mục của yêu cầu.

### Hoàn thành, hủy và ngừng hoạt động

- Chỉ hợp đồng **Đang hiệu lực** mới có thể **Hoàn thành**; không thể hoàn thành nếu vẫn còn lịch chuyến chưa hoàn tất hoặc chưa hủy.
- Có thể **Hủy hợp đồng** khi đang nháp hoặc đang hiệu lực, nhưng không khi đã có lịch chuyến hoặc chứng từ liên quan.
- Chỉ hợp đồng nháp mới có thể **Ngừng hoạt động**.

## Lịch cố định cho hợp đồng nguyên tắc

Trong chi tiết hợp đồng đang hiệu lực, mở phần **Lịch cố định**. Chức năng này tạo quy tắc lặp lại và sau đó sinh lịch chuyến theo quy tắc.

### Tạo quy tắc lịch

1. Kích hoạt hợp đồng trước.
2. Trong **Lịch cố định**, nhấn **Tạo quy tắc**.
3. Chọn **Hạng mục hợp đồng**; có thể chọn tuyến, xe mặc định và tài xế mặc định.
4. Chọn **Hiệu lực từ** và **Hiệu lực đến**.
5. Thêm một hoặc nhiều dòng **Ngày chạy**: thứ, giờ đón, giờ về, tên ca.
6. Nhấn **Lưu quy tắc**.

Khoảng hiệu lực quy tắc phải nằm trong hiệu lực hợp đồng. Xe mặc định phải đúng loại xe của hạng mục; tuyến riêng phải thuộc đúng khách hàng của hợp đồng. Không được trùng cùng thứ và giờ đón. Nếu nhập giờ về, giờ về phải sau giờ đón.

### Sinh lịch chuyến

1. Tại quy tắc đang áp dụng, nhấn **Sinh lịch chuyến**.
2. Chọn **Từ ngày** và **Đến ngày** trong hiệu lực quy tắc và hợp đồng.
3. Nhấn **Sinh lịch**.
4. Đọc kết quả: số lịch đã tạo, số bỏ qua và số xung đột.

Ngày chạy phải có giờ về thì mới sinh được lịch. Sau khi đã sinh lịch chuyến, quy tắc được khóa: không thể sửa, đổi ngày chạy hay ngừng áp dụng quy tắc đó.

## Lưu ý

- Giữ hợp đồng ở trạng thái nháp đến khi kiểm tra xong hạng mục, tiền đặt cọc và thời gian hiệu lực.
- Kích hoạt hợp đồng theo chuyến chỉ nên thực hiện khi đã chắc chắn về nguồn lực, vì bước này có thể sinh lịch chuyến phục vụ điều hành.
- Nếu cần thay đổi một quy tắc đã sinh lịch, xử lý lịch chuyến đã sinh theo nghiệp vụ điều hành thay vì sửa lại quy tắc.
