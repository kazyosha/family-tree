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
Copy-Item .env.example .env
php artisan key:generate
composer install
npm install
npm run build
php artisan test
```

Không commit `.env`, mật khẩu Neon hoặc khóa Cloudflare R2. Máy local không cần Docker hoặc PostgreSQL.

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

## Tài liệu

- Thiết kế: `docs/superpowers/specs/2026-10-26-tet-memory-archive-design.md`
- Kế hoạch nền móng: `docs/superpowers/plans/2026-10-26-tet-memory-foundation.md`
