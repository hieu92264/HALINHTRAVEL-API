# Lộ trình yêu cầu tháng 11

Các thay đổi được triển khai theo thứ tự 01 đến 06. Mỗi đợt phải qua kiểm thử tự động và kiểm thử giao diện trước khi bắt đầu đợt sau.

- Không tự chạy migration, seeder hoặc thay đổi dữ liệu đang dùng.
- Migration phải bổ sung dữ liệu theo hướng không mất dữ liệu và có đường lùi rõ ràng.
- Các số tiền và thông tin xe trên chuyến là snapshot; sửa danh mục không được sửa lịch sử.

| Đợt | Điều kiện hoàn thành |
| --- | --- |
| 01 | Phân biệt được xe công ty, xe đối tác và loại xe |
| 02 | Chọn tuyến và loại xe lấy đúng giá cố định |
| 03 | Hai form xe chạy không còn checkbox nguồn xe độc lập |
| 04 | Một tháng có nhiều bản tổng hợp trước khi chi trả |
| 05 | Công nợ chỉ xuất hiện trong báo cáo và đúng công thức |
| 06 | Mọi báo cáo tải PDF hoặc Excel, không còn DOCX |
