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

## Tài liệu

- Thiết kế: `docs/superpowers/specs/2026-10-26-tet-memory-archive-design.md`
- Kế hoạch nền móng: `docs/superpowers/plans/2026-10-26-tet-memory-foundation.md`
