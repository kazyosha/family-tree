# Tet Memory Archive Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tạo nền móng Laravel nhẹ chạy bằng Laragon ở local, dùng Neon PostgreSQL và Cloudflare R2, được kiểm thử trên GitHub Actions và deploy lên Vercel mà không cần Docker trên máy người dùng.

**Architecture:** Một Laravel 13 monolith render Blade và dùng Alpine.js/Vite cho tương tác nhỏ. Local dùng PHP/Composer/Node sẵn có và kết nối Neon trực tiếp; production dùng image FrankenPHP được Vercel build từ `Dockerfile.vercel`. Database session/cache và R2 object storage giữ mọi trạng thái bền vững vì Vercel container là stateless.

**Tech Stack:** PHP 8.5, Composer 2.8, Laravel 13, Blade, Alpine.js, Vite, Node 24, Neon PostgreSQL, Laravel Flysystem S3 adapter, Cloudflare R2, FrankenPHP, Vercel, GitHub Actions.

**Spec:** `outputs/tet-memory-archive-design.md` (được sao chép vào repo thành `docs/superpowers/specs/2026-10-26-tet-memory-archive-design.md`).

## Global Constraints

- Repository đích duy nhất: `https://github.com/kazyosha/family-tree.git`.
- Workspace triển khai local: `work/family-tree`; không đặt dependency hoặc build output trong `outputs/`.
- Không cài hoặc chạy Docker, PostgreSQL, Java hay Maven trên máy local.
- Dùng PHP >= 8.3; môi trường hiện có PHP 8.5.0, Composer 2.8.12, Node 24.19.0 và npm 11.17.0.
- Local development chạy bằng Laragon hoặc PHP CLI hiện có; production image chỉ được build trong GitHub Actions/Vercel.
- Neon chứa dữ liệu quan hệ; Cloudflare R2 private bucket chứa media; không lưu media trên filesystem của Vercel.
- Secret chỉ nằm trong `.env` local, GitHub Secrets hoặc Vercel Environment Variables; không commit secret.
- Plan này chỉ tạo nền móng chạy được và kết nối dịch vụ. Authentication, album, upload multipart, gallery, player và thùng rác được tách thành các plan tính năng tiếp theo.

## Review Focus

- PHP CLI có thể đang lấy từ Herd thay vì Laragon; Task 1 buộc kiểm tra extension cần thiết trên đúng executable dùng để chạy dự án.
- Neon URL có ký tự đặc biệt hoặc yêu cầu TLS; Task 2 có bài kiểm tra kết nối/migration và tài liệu dùng các biến `DB_*` riêng thay vì tự parse URL.
- Vercel container không có filesystem bền vững; Task 2 kiểm tra session/cache dùng database và Task 5 kiểm tra cấu hình log ra stderr.
- R2 bucket phải luôn private và credentials không được lộ cho browser; Task 3 chỉ kiểm thử adapter server-side và cấu hình endpoint/path-style chính xác.
- Local không build container nên sai Dockerfile có thể chỉ xuất hiện muộn; Task 5 bắt GitHub Actions build image trên mọi push trước khi deploy.

---

## File Structure

```text
work/family-tree/
├── app/
│   ├── Console/Commands/StorageCheckCommand.php
│   └── Http/Controllers/HomeController.php
├── bootstrap/app.php
├── config/
│   ├── database.php
│   ├── filesystems.php
│   ├── logging.php
│   └── session.php
├── database/migrations/                  # Laravel defaults + app bootstrap migration
├── docs/superpowers/
│   ├── specs/2026-10-26-tet-memory-archive-design.md
│   └── plans/2026-10-26-tet-memory-foundation.md
├── resources/
│   ├── css/app.css
│   ├── js/app.js
│   └── views/home.blade.php
├── routes/
│   ├── console.php
│   └── web.php
├── tests/
│   ├── Feature/HomePageTest.php
│   ├── Feature/PersistenceConfigurationTest.php
│   └── Feature/StorageCheckCommandTest.php
├── .dockerignore
├── .env.example
├── .github/workflows/ci.yml
├── .gitignore
├── Caddyfile
├── Dockerfile.vercel
├── README.md
├── composer.json
├── composer.lock
├── package.json
├── package-lock.json
└── vercel.json
```

### Task 1: Repository and Laravel Baseline

**Files:**
- Create: all standard Laravel 13 skeleton files under `work/family-tree/`
- Create: `docs/superpowers/specs/2026-10-26-tet-memory-archive-design.md`
- Create: `docs/superpowers/plans/2026-10-26-tet-memory-foundation.md`
- Modify: `.gitignore`
- Modify: `.env.example`
- Modify: `README.md`

**Interfaces:**
- Consumes: approved design, this plan, PHP 8.5, Composer 2.8, Node 24, Git remote.
- Produces: Laravel 13 repository on branch `main` with reproducible Composer/npm lockfiles.

- [ ] **Step 1: Verify the lightweight local toolchain**

Run from PowerShell:

```powershell
php -v
composer --version
node --version
npm --version
php -m
```

Expected: PHP >= 8.3, Composer 2.x, Node 24.x; PHP modules include `ctype`, `curl`, `dom`, `fileinfo`, `mbstring`, `openssl`, `pdo`, `pdo_pgsql`, `session`, `tokenizer`, and `xml`. If Laragon uses a different PHP executable, select PHP 8.5 in Laragon and repeat with that executable before continuing.

- [ ] **Step 2: Create the Laravel project without Docker**

Create Laravel 13 in a temporary empty directory, then place it at `work/family-tree`; do not scaffold React/Vue/Livewire. Install dependencies normally so `composer.lock` and `package-lock.json` pin the resolved versions.

```powershell
composer create-project laravel/laravel work/family-tree "13.*"
Set-Location work/family-tree
npm install
```

Expected: `php artisan --version` reports Laravel Framework 13.x and `npm run build` succeeds.

- [ ] **Step 3: Initialize Git and attach the requested remote**

```powershell
git init -b main
git remote add origin https://github.com/kazyosha/family-tree.git
git remote -v
```

Expected: fetch and push URLs both match the requested repository. The remote currently has no refs; do not merge an unrelated history.

- [ ] **Step 4: Copy the approved design and plan into the repository**

Copy the two approved files from `outputs/` to the exact `docs/superpowers/` paths above. Extend `.gitignore` to ignore `.env*` while allowing `.env.example`, plus `vendor/`, `node_modules/`, `public/build/`, `.phpunit.cache/`, and `.vercel/`.

- [ ] **Step 5: Run baseline verification**

```powershell
php artisan test
npm run build
git check-ignore .env node_modules vendor public/build
```

Expected: default tests PASS, Vite build succeeds, and all disposable/secret paths are ignored.

- [ ] **Step 6: Commit**

```powershell
git add .
git commit -m "chore: initialize Laravel Tet memory archive"
```

### Task 2: Neon PostgreSQL and Stateless Laravel Configuration

**Files:**
- Modify: `.env.example`
- Modify: `config/database.php`
- Modify: `config/session.php`
- Modify: `config/cache.php`
- Modify: `config/logging.php`
- Create: `tests/Feature/PersistenceConfigurationTest.php`
- Modify: `README.md`

**Interfaces:**
- Consumes: `DB_HOST`, `DB_PORT=5432`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_SSLMODE=require` from Neon pooled connection details.
- Produces: PostgreSQL-backed application, database sessions/cache, stderr production logs, successful migrations against Neon.

- [ ] **Step 1: Write failing configuration tests**

Create `PersistenceConfigurationTest` with tests named:

- `production_uses_pgsql_database_sessions_and_database_cache`: set `APP_ENV=production`, refresh config, assert default database `pgsql`, session driver `database`, cache default `database`, and log channel `stderr`.
- `pgsql_requires_tls`: assert the pgsql connection options resolve `sslmode` to `require` when `DB_SSLMODE=require`.
- `local_test_environment_remains_isolated`: assert PHPUnit uses SQLite in-memory and never reads production Neon credentials.

- [ ] **Step 2: Run tests and confirm failure**

```powershell
php artisan test --filter=PersistenceConfigurationTest
```

Expected: FAIL because the production stateless defaults and TLS assertion are not configured.

- [ ] **Step 3: Implement stateless persistence configuration**

Set production defaults through config/env to PostgreSQL, database session, database cache, and stderr logs. Keep tests on SQLite `:memory:` via `phpunit.xml`. Use discrete `DB_*` variables copied from Neon’s pooled endpoint and pass `sslmode=require`; do not parse or commit a full credential URL.

- [ ] **Step 4: Run isolated tests**

```powershell
php artisan test --filter=PersistenceConfigurationTest
php artisan test
```

Expected: all tests PASS without contacting Neon.

- [ ] **Step 5: Verify the real Neon connection manually**

Copy `.env.example` to `.env`, fill only local secrets, run:

```powershell
php artisan config:clear
php artisan migrate:status
php artisan migrate --force
php artisan about --only=environment,cache,drivers
```

Expected: TLS connection succeeds through the pooled Neon hostname; Laravel default users/cache/jobs/sessions migrations apply once.

- [ ] **Step 6: Commit**

```powershell
git add .env.example config tests README.md
git commit -m "feat: configure Neon persistence"
```

### Task 3: Cloudflare R2 Storage Adapter

**Files:**
- Modify: `composer.json`
- Modify: `composer.lock`
- Modify: `.env.example`
- Modify: `config/filesystems.php`
- Create: `app/Console/Commands/StorageCheckCommand.php`
- Create: `tests/Feature/StorageCheckCommandTest.php`
- Modify: `README.md`

**Interfaces:**
- Consumes: `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_BUCKET`, `R2_ENDPOINT`, `R2_REGION=auto`.
- Produces: private `r2` filesystem disk and `php artisan app:storage-check` command that validates put/head/get/delete without exposing credentials.

- [ ] **Step 1: Install the S3-compatible Flysystem adapter**

```powershell
composer require league/flysystem-aws-s3-v3
```

Expected: Composer updates only the required adapter/AWS dependencies and records them in the lockfile.

- [ ] **Step 2: Write failing storage command tests**

With `Storage::fake('r2')`, assert `app:storage-check` writes a random probe object under `_health/`, verifies the exact content, deletes it, exits `0`, and prints `R2 storage check passed`. Add a failing fake disk case asserting nonzero exit and a message that contains no access key, secret, endpoint query string, or stack trace.

- [ ] **Step 3: Run and confirm failure**

```powershell
php artisan test --filter=StorageCheckCommandTest
```

Expected: FAIL because the disk and command do not exist.

- [ ] **Step 4: Implement the R2 disk and command**

Add disk `r2` using driver `s3`, endpoint from `R2_ENDPOINT`, region `auto`, private visibility, `throw=true`, and path-style endpoint disabled. Implement `StorageCheckCommand.handle(): int` using `Storage::disk('r2')`; always delete the probe in `finally` when it was created.

- [ ] **Step 5: Verify locally with fake and real R2**

```powershell
php artisan test --filter=StorageCheckCommandTest
php artisan app:storage-check
```

Expected: test PASS; real command reports success and leaves no `_health/` object. Confirm the R2 bucket has no public development URL or custom public domain enabled.

- [ ] **Step 6: Commit**

```powershell
git add composer.json composer.lock .env.example config app/Console tests README.md
git commit -m "feat: configure private R2 storage"
```

### Task 4: Minimal Blade Application Shell for Laragon

**Files:**
- Create: `app/Http/Controllers/HomeController.php`
- Modify: `routes/web.php`
- Create: `resources/views/home.blade.php`
- Modify: `resources/css/app.css`
- Modify: `resources/js/app.js`
- Create: `tests/Feature/HomePageTest.php`
- Modify: `README.md`

**Interfaces:**
- Consumes: Laravel Blade/Vite baseline.
- Produces: `GET /` HTML page titled `Ký ức Tết`, `GET /up` framework health endpoint, responsive shell ready for later authentication and album features.

- [ ] **Step 1: Write the failing home-page test**

Assert `GET /` returns 200, contains `Ký ức Tết`, includes the Vite asset tags, and does not expose `APP_KEY`, database host, R2 endpoint, or debug stack traces. Assert `GET /up` returns 200.

- [ ] **Step 2: Run and confirm failure**

```powershell
php artisan test --filter=HomePageTest
```

Expected: FAIL because the requested home shell does not exist.

- [ ] **Step 3: Implement the minimal shell**

Create invokable `HomeController.__invoke(): View`, register `/`, and render a mobile-first Blade page with one heading and a short placeholder stating that private Tết albums will appear after sign-in. Load only the project CSS/JS through Vite; initialize Alpine.js in `resources/js/app.js` without adding a SPA framework.

- [ ] **Step 4: Verify through CLI and Laragon**

```powershell
php artisan test --filter=HomePageTest
npm run build
```

Then use Laragon to point a local site at `work/family-tree/public` and open its generated `.test` hostname. Expected: home page and `/up` render; PHP errors are absent; network tab loads built CSS/JS.

- [ ] **Step 5: Commit**

```powershell
git add app/Http routes resources tests README.md
git commit -m "feat: add Tet memory application shell"
```

### Task 5: Cloud-Only Vercel Container Build and CI

**Files:**
- Create: `Dockerfile.vercel`
- Create: `Caddyfile`
- Create: `.dockerignore`
- Create: `vercel.json`
- Create: `.github/workflows/ci.yml`
- Modify: `README.md`

**Interfaces:**
- Consumes: Laravel app, Composer/npm lockfiles, Vercel environment variables.
- Produces: FrankenPHP HTTP container listening on Vercel `PORT`, built remotely; CI validates PHP, frontend assets and image without local Docker.

- [ ] **Step 1: Add a production configuration test**

Extend `PersistenceConfigurationTest` to assert production has `APP_DEBUG=false`, log channel `stderr`, session/cache database drivers, and no filesystem session/cache dependency.

- [ ] **Step 2: Create the Vercel production image definition**

Use a Node 24 build stage for `npm ci && npm run build`, a Composer 2 stage for `composer install --no-dev --classmap-authoritative --no-interaction`, and a Debian-based `dunglas/frankenphp:1-php8.5-trixie` runtime. Install only `pdo_pgsql`, `intl`, `zip`, and `opcache`; copy application, vendor and built assets; give the runtime user write access only to `storage/` and `bootstrap/cache/`.

`Caddyfile` must bind to `:{$PORT:80}`, serve root `/app/public`, enable compression, route through `php_server`, disable automatic HTTPS inside Vercel, and never expose dotfiles. `Dockerfile.vercel` must run `frankenphp run --config /etc/caddy/Caddyfile` as its final process.

- [ ] **Step 3: Add Vercel and Docker context configuration**

Create `vercel.json` with only the Vercel schema reference because root `Dockerfile.vercel` is auto-detected. `.dockerignore` must exclude `.env*`, `.git`, `node_modules`, `vendor`, tests, local logs/cache, `outputs`, and `work` while retaining `.env.example` only when documentation needs it.

- [ ] **Step 4: Create GitHub Actions checks**

Create jobs for:

- PHP: install PHP 8.5 extensions, `composer install`, run Pint in test mode and `php artisan test` with SQLite.
- Assets: Node 24, `npm ci`, `npm run build`.
- Container: build `Dockerfile.vercel` using GitHub-hosted Docker, then start it with CI-only PostgreSQL service variables and assert `/up` returns 200.
- Secret scan: fail on tracked `.env` files or literal Neon/R2 credentials; allow placeholders in `.env.example`.

- [ ] **Step 5: Run all checks available without Docker**

```powershell
composer install
php artisan test
npm ci
npm run build
git status --short
```

Expected: PHP tests and asset build PASS; only intended deployment/CI files are changed. Do not attempt the container build locally.

- [ ] **Step 6: Commit and push for remote container verification**

```powershell
git add Dockerfile.vercel Caddyfile .dockerignore vercel.json .github README.md tests
git commit -m "ci: add cloud-only Vercel container build"
git push -u origin main
```

Expected: GitHub Actions builds and smoke-tests the container successfully. Fix CI failures before connecting Vercel.

- [ ] **Step 7: Connect Vercel and verify a preview deployment**

Import `kazyosha/family-tree` from the Vercel dashboard. Configure Preview and Production variables separately: `APP_KEY`, `APP_ENV`, `APP_DEBUG=false`, `APP_URL`, all Neon `DB_*`, `SESSION_DRIVER=database`, `CACHE_STORE=database`, `LOG_CHANNEL=stderr`, and all R2 variables. Deploy a preview from Git; verify `/up`, `/`, database migrations and `app:storage-check` through an approved deployment command path. Promote only after logs contain no secret, write-permission, migration or R2 errors.

## Self-Review Result

- Spec coverage: this foundation plan covers local Laragon/PHP setup, repository creation, Neon, R2, a Blade shell, CI and Vercel deployment. Authentication, roles, annual albums, direct multipart upload, gallery/player and 30-day trash remain explicitly separate plans.
- Step scan: every development task includes failing test, focused verification and commit; infrastructure-only steps have a concrete observable result.
- Type consistency: environment names and `r2` disk name remain identical across config, command, tests, CI and Vercel.
- Review focus: the five likely failure classes are each attached to a task test or CI check.
- Proportion: implementation bodies are left to the executor; the plan pins only required interfaces, commands and observable behavior.
