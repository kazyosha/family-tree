# Ký ức Tết

Kho lưu giữ ảnh, video và âm thanh của gia đình theo từng dịp Tết.

## Công nghệ

- Laravel 13, Blade và Alpine.js
- Neon PostgreSQL cho dữ liệu ứng dụng
- Cloudflare R2 private bucket cho media
- Vercel + FrankenPHP cho production

## Yêu cầu local

- PHP 8.3 trở lên với `pdo_pgsql`, `zip`, `intl`, `mbstring`, `curl`, `fileinfo`, `openssl` và `xml`
- Composer 2
- Node.js 24 và npm
- Laragon trỏ document root tới thư mục `public/`

## Khởi động

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm install
npm run build
php artisan test
```

Sau đó điền thông tin Neon và R2 như hai mục bên dưới rồi chạy `php artisan migrate`. Không commit `.env`, mật khẩu Neon hoặc khóa Cloudflare R2. Máy local không cần Docker hoặc PostgreSQL cài trực tiếp.

## Neon PostgreSQL

Trong Neon Console, chọn kết nối **Pooled** rồi điền từng thành phần vào `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=ep-...-pooler....neon.tech
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=...
DB_PASSWORD=...
DB_SSLMODE=require
SESSION_DRIVER=database
CACHE_STORE=database
LOG_CHANNEL=stderr
```

Không đặt connection string đầy đủ vào source. Kiểm tra kết nối bằng `php artisan migrate:status`, sau đó chạy `php artisan migrate --force`.

## Tạo chủ sở hữu và đăng nhập

Ứng dụng không có đăng ký công khai. Sau khi cấu hình database, chạy migration rồi tạo tài khoản `OWNER` đầu tiên:

```powershell
php artisan migrate --force
php artisan app:create-owner
```

Command sẽ hỏi họ tên, email, mật khẩu và xác nhận mật khẩu. Mật khẩu được nhập ẩn, được hash trước khi lưu và không được ghi vào output.

- Trang đăng nhập: `/login`
- Không gian riêng sau đăng nhập: `/dashboard`
- Đăng xuất chỉ được thực hiện bằng biểu mẫu `POST` có CSRF protection.
- Chỉ tài khoản có trạng thái hoạt động mới đăng nhập được. Tài khoản không hoạt động nhận cùng thông báo chung như thông tin đăng nhập sai.
- Không có route đăng ký công khai hoặc khôi phục mật khẩu trong chặng hiện tại. Chủ sở hữu chịu trách nhiệm cấp hoặc đặt lại tài khoản thành viên ở tính năng quản lý tiếp theo.

## Cloudflare R2

Tạo private R2 bucket và API token chỉ có quyền đọc/ghi bucket đó. Không bật `r2.dev` hoặc custom public domain. Điền vào `.env`:

```dotenv
R2_ACCESS_KEY_ID=...
R2_SECRET_ACCESS_KEY=...
R2_BUCKET=tet-memory-media
R2_ENDPOINT=https://<ACCOUNT_ID>.r2.cloudflarestorage.com
R2_REGION=auto
```

Kiểm tra kết nối bằng `php artisan app:storage-check`. Command tạo một probe tạm, đọc lại rồi xóa ngay.

## Deploy Vercel bằng container

Vercel tự phát hiện `Dockerfile.vercel` ở thư mục gốc. Image dùng FrankenPHP và chỉ ghi vào `storage/` cùng `bootstrap/cache/`; dữ liệu lâu dài vẫn nằm ở Neon và R2.

1. Push repo lên GitHub và đợi bốn job CI xanh: PHP, frontend assets, Vercel container và secret scan.
2. Import repo `kazyosha/family-tree` trong Vercel.
3. Tạo biến riêng cho Preview và Production: `APP_KEY`, `APP_ENV`, `APP_DEBUG=false`, `APP_URL`, toàn bộ `DB_*`, `SESSION_DRIVER=database`, `CACHE_STORE=database`, `LOG_CHANNEL=stderr` và toàn bộ biến `R2_*`.
4. Chạy migration như một release step riêng bằng `php artisan migrate --force`. Không chạy migration khi container khởi động vì nhiều instance có thể cùng chạy một lúc.
5. Kiểm tra `/up`, `/` và `php artisan app:storage-check` trước khi đưa Preview lên Production.

Không dùng chung `APP_KEY` giữa Preview và Production. Không chạy `php artisan optimize` trong lúc build vì Vercel chỉ cấp các biến bí mật khi container chạy.

Tham khảo: [Vercel hướng dẫn triển khai Laravel với FrankenPHP](https://vercel.com/kb/guide/laravel-php-with-docker).

## Tài liệu

- Thiết kế: `docs/superpowers/specs/2026-10-26-tet-memory-archive-design.md`
- Kế hoạch nền móng: `docs/superpowers/plans/2026-10-26-tet-memory-foundation.md`
