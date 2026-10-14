# Hướng dẫn sử dụng Hà Linh Travel

> Phiên bản tài liệu: 14/10/2026  
> Đối tượng: nhân viên kinh doanh, điều hành, kế toán, quản lý, quản trị viên và tài xế sử dụng hệ thống nội bộ Hà Linh Travel.

Hà Linh Travel là hệ thống quản lý vận hành xe. Hệ thống hỗ trợ quản lý danh mục dùng chung, yêu cầu thuê xe, báo giá, hợp đồng, lịch chuyến, lệnh điều xe, thu chi, công nợ, lương tài xế và báo cáo.

Tài liệu này được biên soạn từ giao diện và quy tắc xử lý hiện có trong phiên bản phần mềm hiện tại. Tên màn hình, nút bấm và trạng thái được giữ theo đúng giao diện tiếng Việt.

## Bắt đầu nhanh

1. Đăng nhập bằng tài khoản được quản trị viên cấp.
2. Kiểm tra menu bên trái: các mục xuất hiện tùy theo quyền của tài khoản.
3. Chuẩn bị danh mục nền tảng: khách hàng, đối tác, loại xe, xe, tài xế, tuyến xe và bảng giá.
4. Theo luồng nghiệp vụ phù hợp: thuê xe, điều hành, tài chính hoặc lương.

Xem [Bắt đầu sử dụng](01-getting-started.md) và [Vai trò và quyền hạn](02-roles-and-permissions.md) trước khi thực hiện nghiệp vụ.

## Mục lục theo công việc

| Công việc | Tài liệu |
|---|---|
| Đăng nhập, đọc Tổng quan và dùng danh sách dữ liệu | [01 – Bắt đầu sử dụng](01-getting-started.md) |
| Biết quyền của từng vai trò | [02 – Vai trò và quyền hạn](02-roles-and-permissions.md) |
| Khai báo khách hàng, xe, tài xế, tuyến và bảng giá | [03 – Danh mục dùng chung](03-master-data.md) |
| Xử lý yêu cầu thuê xe, báo giá và phản hồi của khách | [04 – Thuê xe và báo giá](04-rental-and-quotation.md) |
| Lập, kích hoạt và thực hiện hợp đồng | [05 – Hợp đồng và lịch cố định](05-contracts-and-schedules.md) |
| Phân công, phát hành lệnh và chốt chuyến | [06 – Điều hành và lệnh điều xe](06-dispatch.md) |
| Thao tác của tài xế trong không gian cá nhân | [07 – Không gian tài xế](07-driver-workspace.md) |
| Ghi nhận thu chi, công nợ, tạm ứng và lương | [08 – Tài chính và nhân sự](08-finance-and-payroll.md) |
| Xem, lọc và xuất báo cáo | [09 – Báo cáo](09-reports.md) |
| Làm việc theo luồng đầu-cuối | [Quy trình nghiệp vụ](workflows/operations-workflows.md) |
| Xử lý các tình huống thường gặp | [Khắc phục sự cố](troubleshooting.md) |

## Quy ước trong tài liệu

- **Menu > Mục** là đường dẫn ở thanh điều hướng bên trái.
- **Nút bấm** và **trạng thái** được in đậm.
- Những thao tác làm dữ liệu không thể sửa tiếp, như khóa chứng từ hoặc chốt lương, luôn cần được kiểm tra kỹ trước khi xác nhận.
- Nếu không thấy màn hình hoặc nút được nêu, tài khoản hiện tại chưa có quyền cần thiết. Hãy liên hệ quản trị viên.

## Luồng vận hành tổng quát

```text
Danh mục dùng chung
        ↓
Yêu cầu thuê xe → Báo giá → Khách chấp nhận → Hợp đồng
                                                  ↓
                              Lịch chuyến → Phân công → Lệnh điều xe
                                                  ↓
                           Báo cáo hoàn tất → Xác nhận → Thu chi / công lương
                                                  ↓
                                       Đối soát và báo cáo
```

Tùy loại dịch vụ, hợp đồng nguyên tắc có thể dùng **Lịch cố định** để sinh nhiều lịch chuyến; hợp đồng theo chuyến tạo từ báo giá đã chấp nhận sẽ tạo lịch chuyến khi được kích hoạt.
