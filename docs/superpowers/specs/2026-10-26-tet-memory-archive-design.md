# Thiết kế MVP kho ký ức Tết gia đình

Ngày: 2026-10-26

## 1. Mục đích

Xây dựng một webapp riêng tư để gia đình lưu giữ và xem lại ảnh, video, âm thanh trong các dịp Tết theo từng năm. Sản phẩm không còn chức năng cây gia phả, hồ sơ dòng họ hay mô hình quan hệ huyết thống.

Trải nghiệm cốt lõi là mở một năm, xem ảnh dạng thư viện và phát video hoặc âm thanh ngay trong trình duyệt.

## 2. Người dùng và quyền hạn

- `OWNER`: tạo kho gia đình, tạo tài khoản hoặc lời mời, phân quyền, tạo album năm, quản lý và xóa nội dung.
- `EDITOR`: tạo/chỉnh sửa album, tải media lên, sửa chú thích và sắp xếp nội dung.
- `VIEWER`: phải đăng nhập; chỉ được xem ảnh và phát video/âm thanh.
- Không có đăng ký công khai, xem ẩn danh, liên kết khách hoặc trang album công khai trong MVP.
- Giao diện không cung cấp chức năng tải file gốc cho `VIEWER`. Đây là hạn chế ở cấp sản phẩm, không phải DRM: hệ thống không thể ngăn tuyệt đối chụp màn hình hoặc ghi/lưu lại dữ liệu đã phát trên thiết bị người xem.

## 3. Phạm vi MVP

### Chức năng bắt buộc

1. Đăng nhập bằng email và mật khẩu.
2. Một không gian gia đình duy nhất.
3. Danh sách album Tết, mỗi album tương ứng một năm âm lịch/dương lịch được đặt tên bởi quản trị viên, ví dụ `Tết 2026`.
4. Ảnh bìa, lời giới thiệu và trạng thái nháp/xuất bản cho mỗi album.
5. Tải trực tiếp ảnh, video và âm thanh từ trình duyệt lên private bucket.
6. Media có tên hiển thị, chú thích, thời điểm ghi nhận nếu biết, loại, kích thước và thứ tự trong album.
7. Thư viện ảnh responsive; trình phát video và âm thanh bằng HTML5.
8. `OWNER`/`EDITOR` thêm, sửa, sắp xếp và xóa nội dung; `VIEWER` chỉ xem/phát.
9. URL xem media được ký, có thời hạn ngắn và chỉ cấp sau khi Laravel kiểm tra phiên đăng nhập cùng quyền truy cập.
10. Giao diện ưu tiên điện thoại nhưng sử dụng tốt trên máy tính.
11. Thùng rác giữ album/media đã xóa trong 30 ngày trước khi xóa object vĩnh viễn khỏi R2.

### Không thuộc MVP

- Cây gia phả, sơ đồ quan hệ, hồ sơ cá nhân theo dòng họ.
- Thành viên tự gửi tư liệu chờ duyệt.
- Bình luận, lượt thích, bảng tin hoặc thông báo.
- Chia sẻ công khai, liên kết khách và tải file gốc cho `VIEWER`.
- Nhận diện khuôn mặt, gắn thẻ người, AI tạo nội dung.
- Chỉnh sửa ảnh/video, chuyển mã video hoặc tạo nhiều chất lượng phát trực tuyến.
- Ứng dụng mobile native.
- Nhiều gia đình/tenant trong một bản cài đặt.

## 4. Các phương án đã cân nhắc

### A. React + Spring Boot trong monorepo

Cho trải nghiệm frontend linh hoạt nhưng yêu cầu JDK, Node, Maven/npm cache và quy trình build phức tạp. Không phù hợp với giới hạn khoảng 12 GB dung lượng trống của máy phát triển và phạm vi sản phẩm đã thu gọn.

### B. Laravel API + frontend SPA riêng

Giữ frontend/backend tách biệt nhưng vẫn cần hai toolchain và nhiều dependency. Phạm vi album Tết không cần độ phức tạp này.

### C. Laravel monolith + Blade/Alpine.js — đã chọn

Một codebase PHP nhẹ, chạy thuận tiện trong Laragon và vẫn đủ tương tác cho gallery, upload progress, modal và media player. Asset frontend được build trên cloud; local chỉ cần Node khi thực sự chỉnh CSS/JavaScript hoặc có thể dùng asset đã build trong những phiên làm backend.

## 5. Kiến trúc

- Backend/UI: Laravel monolith, Blade templates và Alpine.js.
- Local: Laragon cung cấp PHP và web server; không cài Docker, không chạy PostgreSQL local.
- Database: Neon PostgreSQL lưu tài khoản, album và metadata media.
- Media: Cloudflare R2 Standard, private bucket, giao tiếp qua S3-compatible API.
- Deploy: Vercel build từ GitHub; ứng dụng Laravel được đóng gói/chạy bằng cơ chế container hoặc runtime phù hợp được Vercel hỗ trợ tại thời điểm triển khai. Máy local không build container.
- CI: GitHub Actions chạy test và build, không phụ thuộc môi trường Laragon.

Browser không gửi file lớn qua Laravel/Vercel. Sau khi `OWNER` hoặc `EDITOR` yêu cầu upload, Laravel xác thực quyền, tạo object key và presigned upload URL. Browser tải trực tiếp lên R2 rồi gọi API hoàn tất để Laravel xác minh object và lưu metadata. Khi xem, Laravel tạo presigned GET URL thời hạn ngắn với nội dung hiển thị inline.

## 6. Lựa chọn lưu trữ

Cloudflare R2 Standard đã được chọn vì có 10 GB-tháng miễn phí, giá sau mức miễn phí thấp, egress trực tiếp miễn phí và tương thích Laravel S3/Flysystem. Neon chỉ lưu metadata, không lưu binary media.

Object key không chứa tên file gốc hoặc thông tin cá nhân; dùng UUID và namespace theo album. Bucket luôn private. Thông tin truy cập R2 chỉ nằm trong biến môi trường của Laravel/Vercel/GitHub Actions.

## 7. Mô hình dữ liệu cấp cao

- `users`: email, password hash, role, trạng thái hoạt động.
- `albums`: năm, tên, mô tả, cover media, trạng thái `DRAFT`/`PUBLISHED`, timestamps.
- `media_items`: album, loại `IMAGE`/`VIDEO`/`AUDIO`, object key, tên hiển thị, chú thích, MIME type, byte size, thời điểm ghi nhận tùy chọn, thứ tự, trạng thái upload, timestamps.
- `upload_sessions`: người tạo, object key, MIME type dự kiến, dung lượng dự kiến, hạn dùng và trạng thái; dùng để tránh ghi metadata cho upload chưa hoàn tất.
- `activity_logs`: người thực hiện, hành động, loại/id đối tượng và thời gian; MVP chỉ cần log thao tác quản trị quan trọng.

## 8. Xác thực và bảo mật

- Dùng Laravel session authentication với cookie `HttpOnly`, `Secure`, `SameSite=Lax` trong production.
- CSRF protection cho mọi thao tác thay đổi dữ liệu.
- Không có endpoint đăng ký công khai.
- `OWNER` đầu tiên được tạo bằng lệnh Artisan chạy một lần hoặc seeder đọc credential từ secret; không hard-code mật khẩu.
- `OWNER` tạo tài khoản thành viên với mật khẩu tạm thời; người dùng bắt buộc đổi mật khẩu trong lần đăng nhập đầu tiên.
- MVP chưa tích hợp email. Khi người dùng quên mật khẩu, `OWNER` tạo mật khẩu tạm mới và hệ thống tiếp tục bắt đổi ở lần đăng nhập kế tiếp.
- Policy Laravel kiểm tra vai trò trên mọi thao tác album/media.
- Presigned URL có thời hạn ngắn; ứng dụng không lưu signed URL trong database.
- Upload bị giới hạn theo allowlist MIME và kích thước; server xác minh metadata object trước khi đánh dấu hoàn tất.
- Giới hạn mặc định: ảnh 25 MB, âm thanh 100 MB, video 2 GB và tối đa 5.000 media trong một album.
- Media lớn hơn 100 MB dùng multipart upload trực tiếp từ browser lên R2 để hỗ trợ retry từng phần.
- MVP chỉ nhận định dạng trình duyệt phát phổ biến: ảnh JPEG/PNG/WebP, video MP4 H.264/AAC hoặc WebM, âm thanh MP3/M4A/WAV. MOV/HEVC và codec không tương thích bị từ chối; MVP không chuyển mã.
- Xóa album/media là soft delete. Object tiếp tục được giữ trong R2 trong 30 ngày để `OWNER` có thể khôi phục, sau đó tác vụ định kỳ xóa vĩnh viễn. Lỗi một phần phải được ghi log và thử dọn lại an toàn.

## 9. Luồng chính

### Quản trị

1. `OWNER` đăng nhập và tạo album `Tết <năm>`.
2. `OWNER`/`EDITOR` chọn media.
3. Laravel cấp URL tải trực tiếp lên R2 cho từng file hợp lệ.
4. Trình duyệt upload và hiển thị tiến độ.
5. Laravel xác minh object, ghi metadata và đưa media vào album.
6. Biên tập viên thêm chú thích, sắp xếp, chọn ảnh bìa và xuất bản album.
7. Nội dung bị xóa chuyển vào thùng rác; chỉ `OWNER` được khôi phục hoặc xóa vĩnh viễn trước hạn.

### Người xem

1. `VIEWER` đăng nhập.
2. Trang chủ hiển thị các album đã xuất bản theo năm giảm dần.
3. Người xem mở album, xem ảnh dạng lưới và mở lightbox.
4. Khi phát video hoặc âm thanh, Laravel kiểm tra quyền rồi cấp URL inline có thời hạn ngắn.
5. Giao diện không hiển thị nút download hoặc URL object cố định.

## 10. Mục tiêu chất lượng

- Hoạt động tốt trên điện thoại phổ biến và kết nối mạng gia đình.
- Upload có progress, báo lỗi rõ và có thể thử lại từng file.
- Album nhiều media dùng phân trang/lazy loading, không tải toàn bộ video khi mở trang.
- Không có media private nào truy cập được bằng URL cố định công khai.
- Database migrations và test chạy được trên GitHub Actions.
- Source checkout và dependency local phù hợp máy còn khoảng 12 GB trống; không yêu cầu Docker local.

## 11. Quyết định giao cho builder

- Chọn phiên bản Laravel/PHP/Node tương thích tốt với Laragon và Vercel tại thời điểm triển khai.
- Chọn UI/CSS layer nhẹ, ưu tiên Blade components và Alpine.js, không thêm SPA framework.
- Chọn package Flysystem S3 và cách tạo presigned URL phù hợp Cloudflare R2.
- Chọn chiến lược thumbnail: tạo phía client trước upload hoặc dịch vụ ngoài; MVP không chuyển mã video.
- Chọn cơ chế tác vụ định kỳ tương thích Vercel để dọn thùng rác quá 30 ngày và retry các object xóa lỗi.

## 12. Tiêu chí hoàn thành MVP

- `OWNER` tạo được album theo năm và cấp tài khoản `EDITOR`/`VIEWER`.
- `OWNER`/`EDITOR` upload được ảnh, video, âm thanh trực tiếp lên R2 và quản lý nội dung album.
- `VIEWER` đăng nhập, xem album đã xuất bản, xem ảnh và phát video/âm thanh nhưng không có chức năng tải file gốc.
- `OWNER` khôi phục được nội dung trong thùng rác 30 ngày; nội dung quá hạn được xóa vĩnh viễn khỏi database và R2.
- Người chưa đăng nhập không xem được album hay nhận được signed media URL.
- Ứng dụng chạy trong Laragon khi phát triển và deploy thành công lên Vercel từ GitHub.
