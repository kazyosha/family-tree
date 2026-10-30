# Thiết kế nền tảng đăng nhập và phân quyền

Ngày: 2026-10-30

## Mục đích

Thêm lớp bảo vệ đầu tiên cho kho ký ức Tết: chỉ tài khoản đang hoạt động mới đăng nhập được, người chưa đăng nhập không thể xem khu vực riêng, và ứng dụng nhận biết ba vai trò `OWNER`, `EDITOR`, `VIEWER` để các tính năng album sau này dùng chung.

## Phạm vi

- Trang đăng nhập bằng email và mật khẩu, không có đăng ký công khai.
- Đăng xuất bằng yêu cầu `POST` có CSRF protection.
- Tài khoản có `role` thuộc `OWNER`, `EDITOR`, `VIEWER` và trạng thái `is_active`.
- Middleware `auth` bảo vệ trang tổng quan riêng; khách truy cập được chuyển tới trang đăng nhập.
- Người đã đăng nhập thấy tên, vai trò và nút đăng xuất trên trang tổng quan.
- Lệnh Artisan tương tác để tạo hoặc cập nhật `OWNER` ban đầu; mật khẩu được nhập ẩn, không hard-code và không ghi vào log.
- Route đăng nhập từ chối tài khoản không hoạt động bằng thông báo chung, không tiết lộ email có tồn tại hay không.
- Session được tạo lại sau khi đăng nhập và vô hiệu hóa sau khi đăng xuất.

## Chưa làm trong chặng này

- Màn hình tạo, sửa hoặc khóa thành viên.
- Bắt buộc đổi mật khẩu lần đầu, đặt lại mật khẩu hoặc gửi email.
- Album, upload R2, gallery và trình phát media.
- Phân quyền thao tác cụ thể theo vai trò; chặng này chỉ lưu và hiển thị vai trò để policy sau sử dụng.

## Phương án đã cân nhắc

### Laravel session thủ công — đã chọn

Dùng trực tiếp guard `web`, `Auth::attempt`, middleware `guest`/`auth`, controller và Blade. Phương án này nhẹ, không thêm package giao diện và phù hợp chính sách không đăng ký công khai.

### Laravel Breeze

Scaffold nhanh nhưng tạo thêm luồng đăng ký, quên mật khẩu và các màn hình không thuộc phạm vi. Việc cắt bỏ chúng làm tăng bề mặt thay đổi mà không tạo thêm giá trị cho MVP hiện tại.

## Cấu trúc dự kiến

- Migration bổ sung `role` và `is_active` cho `users` với mặc định an toàn.
- Enum `UserRole` định nghĩa ba vai trò hợp lệ.
- `LoginController` xử lý hiển thị form, xác thực thông tin đăng nhập và đăng xuất.
- Form Request chịu trách nhiệm validation và thực hiện xác thực với điều kiện tài khoản hoạt động.
- `DashboardController` render khu vực riêng tạm thời, sẵn sàng thay bằng danh sách album.
- Command `app:create-owner` tạo/cập nhật chủ sở hữu đầu tiên.
- Feature tests bao phủ khách, đăng nhập đúng/sai, tài khoản bị khóa, đăng xuất, bảo vệ route và tạo OWNER.

## Ràng buộc và bảo mật

- Giữ Laravel monolith, Blade và session authentication; không thêm SPA hoặc auth package.
- Không lưu mật khẩu thuần; dùng cast `hashed` hiện có.
- Không có route `register`.
- Thông báo đăng nhập thất bại không phân biệt sai mật khẩu, email không tồn tại hay tài khoản bị khóa.
- Dùng database session theo cấu hình production hiện có.
- Giao diện giữ cùng ngôn ngữ thiết kế của trang `Ký ức Tết` và dùng tốt trên điện thoại.

## Tiêu chí hoàn thành

- `app:create-owner` tạo được một tài khoản `OWNER` đang hoạt động mà không lộ mật khẩu.
- Tài khoản hoạt động đăng nhập và đăng xuất được.
- Tài khoản không hoạt động và thông tin sai đều bị từ chối bằng cùng một thông báo.
- Khách không mở được trang tổng quan; người đã đăng nhập không quay lại trang đăng nhập.
- Không tồn tại đăng ký công khai.
- Toàn bộ test PHP và build frontend hiện có vẫn thành công.
