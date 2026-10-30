# Authentication Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm đăng nhập/đăng xuất bằng Laravel session, ba vai trò nền tảng, trạng thái tài khoản và lệnh tạo `OWNER` đầu tiên cho kho ký ức Tết.

**Architecture:** Laravel dùng guard `web`, middleware `guest`/`auth` và Blade, không cài auth scaffold. `LoginRequest` gom validation, chuẩn hóa email và xác thực tài khoản hoạt động; `UserRole` cùng migration mở rộng mô hình người dùng; lệnh Artisan tạo hoặc cập nhật `OWNER` mà không ghi mật khẩu ra output.

**Tech Stack:** PHP 8.5, Laravel 13, Blade, Laravel session authentication, PHPUnit 12, Vite.

**Spec:** `docs/superpowers/specs/2026-10-30-authentication-foundation-design.md`

## Global Constraints

- Không có route đăng ký công khai, quên mật khẩu hoặc gửi email.
- Chỉ dùng Laravel monolith, Blade và session guard `web`; không thêm Breeze, Jetstream, SPA framework hoặc auth package.
- Vai trò hợp lệ là `OWNER`, `EDITOR`, `VIEWER`; tài khoản mới mặc định `VIEWER` và không hoạt động cho đến khi được cấp quyền rõ ràng.
- Mọi lỗi đăng nhập dùng cùng thông báo `Thông tin đăng nhập không hợp lệ.`
- Mật khẩu luôn được hash, không hard-code, không hiển thị trong console, log hoặc response.
- Đăng nhập phải regenerate session; đăng xuất phải invalidate session và regenerate CSRF token.
- Giữ nguyên trang giới thiệu công khai `/`; khu vực riêng đầu tiên là `/dashboard`.
- Trước khi sửa mã ứng dụng, cài Laravel Boost theo `AGENTS.md`, đọc lại `AGENTS.md` được tạo và tuân thủ các hướng dẫn mới.

## Review Focus

- Email có chữ hoa hoặc khoảng trắng phải được chuẩn hóa trước khi xác thực; Task 2 có test `email_is_normalized_before_authentication`.
- Tài khoản tồn tại nhưng `is_active=false` phải nhận đúng cùng thông báo với email không tồn tại; Task 2 có test `inactive_account_receives_generic_failure`.
- Đăng nhập thành công phải đổi session ID để chống session fixation; Task 2 có test `successful_login_regenerates_the_session`.
- Lệnh tạo OWNER chạy lại với email cũ phải cập nhật đúng một user thay vì tạo trùng; Task 1 có test `create_owner_command_updates_an_existing_user`.
- Route đăng xuất phải từ chối `GET` và yêu cầu CSRF trong HTTP thực; Task 2 kiểm tra không có route `GET /logout`, còn middleware web mặc định bảo vệ `POST /logout`.

---

## File Structure

```text
app/
├── Console/Commands/CreateOwnerCommand.php
├── Enums/UserRole.php
├── Http/Controllers/Auth/LoginController.php
├── Http/Controllers/DashboardController.php
├── Http/Requests/Auth/LoginRequest.php
└── Models/User.php
database/migrations/<timestamp>_add_access_fields_to_users_table.php
resources/views/auth/login.blade.php
resources/views/dashboard.blade.php
resources/views/home.blade.php
resources/css/app.css
routes/web.php
tests/Feature/Auth/LoginTest.php
tests/Feature/Console/CreateOwnerCommandTest.php
tests/Feature/DashboardTest.php
README.md
```

### Task 1: Role Model and Initial OWNER Command

**Files:**
- Modify: `composer.json`
- Modify: `composer.lock`
- Modify: `AGENTS.md`
- Create: `app/Enums/UserRole.php`
- Create: `database/migrations/<timestamp>_add_access_fields_to_users_table.php`
- Modify: `app/Models/User.php`
- Modify: `database/factories/UserFactory.php`
- Create: `app/Console/Commands/CreateOwnerCommand.php`
- Create: `tests/Feature/Console/CreateOwnerCommandTest.php`

**Interfaces:**
- Consumes: Laravel `User` model and the existing `users` table.
- Produces: enum `App\Enums\UserRole: string`, fields `users.role` and `users.is_active`, and command `app:create-owner`.

- [ ] **Step 1: Install the repository-required development guidance**

Run: `composer require laravel/boost --dev && php artisan boost:install`

Expected: Boost installs successfully. Read the regenerated `AGENTS.md` completely before continuing and adjust only implementation details that conflict with its Laravel-version guidance; do not expand product scope.

- [ ] **Step 2: Write failing command and model tests**

Create tests named `create_owner_command_creates_an_active_owner_without_echoing_password` and `create_owner_command_updates_an_existing_user`. Drive the command with name, normalized email, hidden password and password confirmation; assert one user, `role === UserRole::Owner`, `is_active === true`, `Hash::check(...) === true`, and output excludes the plaintext password. Add assertions that factory defaults are `VIEWER` and inactive.

- [ ] **Step 3: Run the focused tests and verify RED**

Run: `php artisan test tests/Feature/Console/CreateOwnerCommandTest.php`

Expected: FAIL because `UserRole`, access fields and `app:create-owner` do not exist.

- [ ] **Step 4: Implement the role, migration, casts, factory states and command**

Create backed enum cases `Owner = 'OWNER'`, `Editor = 'EDITOR'`, `Viewer = 'VIEWER'`. Add string `role` defaulting to `VIEWER` and boolean `is_active` defaulting to `false`; cast them to `UserRole` and `boolean`. Implement `CreateOwnerCommand::handle(): int` using `secret()` for password entry, confirmation validation, `User::updateOrCreate()` by normalized email, role `Owner` and active status.

- [ ] **Step 5: Run migrations and verify GREEN**

Run: `php artisan migrate --force && php artisan test tests/Feature/Console/CreateOwnerCommandTest.php`

Expected: migrations complete and focused tests PASS.

- [ ] **Step 6: Commit Task 1**

Run: `git add AGENTS.md composer.json composer.lock app database tests/Feature/Console && git commit -m "feat: add user roles and owner bootstrap command"`

### Task 2: Session Authentication Backend

**Files:**
- Create: `app/Http/Requests/Auth/LoginRequest.php`
- Create: `app/Http/Controllers/Auth/LoginController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Auth/LoginTest.php`

**Interfaces:**
- Consumes: active `User` records and Laravel guard `web`.
- Produces: named routes `login` (`GET /login`), `login.store` (`POST /login`) and `logout` (`POST /logout`); methods `LoginRequest::authenticate(): void`, `LoginController::create(): View`, `store(LoginRequest): RedirectResponse`, and `destroy(Request): RedirectResponse`.

- [ ] **Step 1: Write failing authentication tests**

Add tests: guest sees login form; active user can authenticate; invalid credentials fail with the generic Vietnamese message; inactive user receives the same message; email is trimmed and lowercased; successful login changes the session ID; authenticated user visiting `/login` redirects to `/dashboard`; logout invalidates authentication and redirects home; `GET /logout` returns 405; route collection contains no `register` route.

- [ ] **Step 2: Run focused tests and verify RED**

Run: `php artisan test tests/Feature/Auth/LoginTest.php`

Expected: FAIL because login/logout routes and handlers do not exist.

- [ ] **Step 3: Implement `LoginRequest`**

Define rules for required valid email and required string password. In `prepareForValidation()` trim/lowercase email. In `authenticate()` call `Auth::attempt(['email' => ..., 'password' => ..., 'is_active' => true], remember: false)` and throw `ValidationException::withMessages(['email' => 'Thông tin đăng nhập không hợp lệ.'])` on every failure.

- [ ] **Step 4: Implement controller and routes**

Put `GET/POST /login` in `guest` middleware and `POST /logout` in `auth` middleware. On login call `authenticate()`, regenerate session, then redirect to `intended(route('dashboard'))`; on logout call `Auth::logout()`, invalidate session, regenerate token, then redirect to `home`.

- [ ] **Step 5: Run focused tests and verify GREEN**

Run: `php artisan test tests/Feature/Auth/LoginTest.php`

Expected: all authentication tests PASS.

- [ ] **Step 6: Commit Task 2**

Run: `git add app/Http/Controllers/Auth app/Http/Requests/Auth routes/web.php tests/Feature/Auth && git commit -m "feat: add private session authentication"`

### Task 3: Login and Private Dashboard UI

**Files:**
- Create: `app/Http/Controllers/DashboardController.php`
- Create: `resources/views/auth/login.blade.php`
- Create: `resources/views/dashboard.blade.php`
- Modify: `resources/views/home.blade.php`
- Modify: `resources/css/app.css`
- Create: `tests/Feature/DashboardTest.php`

**Interfaces:**
- Consumes: named auth routes and authenticated `User` with `UserRole`.
- Produces: named route `dashboard` (`GET /dashboard`) protected by `auth`, responsive login form, private dashboard shell, and a login call-to-action on the public home page.

- [ ] **Step 1: Write failing dashboard and view tests**

Assert guests visiting `/dashboard` redirect to `login`; authenticated users receive 200 and see their escaped name plus Vietnamese role label; the login page contains email/password inputs, CSRF field, submit text `Đăng nhập`, no registration link, and no sensitive configuration; the public home page links to `login`; the dashboard includes a `POST` logout form and no media download control.

- [ ] **Step 2: Run focused tests and verify RED**

Run: `php artisan test tests/Feature/DashboardTest.php tests/Feature/Auth/LoginTest.php`

Expected: FAIL because dashboard route/controller/views and login view do not exist.

- [ ] **Step 3: Implement dashboard route and controller**

Implement invokable `DashboardController::__invoke(): View` and register `GET /dashboard` with `auth` middleware and name `dashboard`.

- [ ] **Step 4: Implement Blade views and styling**

Reuse the visual language already in `home.blade.php`: warm neutral background, red accent, responsive card layout and visible focus states. Render validation errors without echoing password, use `autocomplete="email"` and `autocomplete="current-password"`, add a login link to the home header, and show the authenticated user's role through an explicit `UserRole` label mapping.

- [ ] **Step 5: Run view tests and frontend build**

Run: `php artisan test tests/Feature/DashboardTest.php tests/Feature/Auth/LoginTest.php && npm run build`

Expected: focused tests PASS and Vite build succeeds without errors.

- [ ] **Step 6: Commit Task 3**

Run: `git add app/Http/Controllers/DashboardController.php routes/web.php resources tests/Feature/DashboardTest.php && git commit -m "feat: add login and private dashboard views"`

### Task 4: Documentation and Full Verification

**Files:**
- Modify: `README.md`
- Modify: `graphify-out/` only through Graphify update if the directory remains intentionally local; do not commit it.

**Interfaces:**
- Consumes: completed authentication feature.
- Produces: operator instructions for creating the first owner and evidence that the whole application remains healthy.

- [ ] **Step 1: Document first-owner and local-login workflow**

Add exact commands `php artisan migrate --force` and `php artisan app:create-owner`, then document `/login`, `/dashboard`, lack of public registration, and the rule that inactive accounts cannot authenticate. Do not include example real credentials.

- [ ] **Step 2: Run formatter and full verification**

Run: `vendor/bin/pint --test && php artisan test && npm run build && php artisan route:list`

Expected: Pint reports no style issues, every PHP test passes, Vite build succeeds, routes include only `GET/POST login`, `POST logout`, protected dashboard and existing application routes, with no `register` route.

- [ ] **Step 3: Smoke-test the running local app**

Use a temporary local owner created through the command, open `/login`, sign in, confirm `/dashboard`, sign out, and verify `/dashboard` redirects back to `/login`. Do not retain or report the temporary plaintext password.

- [ ] **Step 4: Commit documentation**

Run: `git add README.md && git commit -m "docs: explain owner login workflow"`

## Self-Review Result

- Spec coverage: all approved behaviors map to Tasks 1–3; member administration, forced password change and album features remain excluded.
- Step scan: every product-code step follows a focused RED/GREEN cycle; setup and documentation are attached to the deliverable that requires them.
- Type consistency: `UserRole`, `role`, `is_active`, route names and controller signatures are identical across tasks.
- Review focus: normalization, generic inactive failure, session regeneration, idempotent owner creation and POST-only logout each have an explicit test.
- Proportion: the plan pins interfaces, observable copy and security behavior while leaving idiomatic Laravel method bodies to implementation.
