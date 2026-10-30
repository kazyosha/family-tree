<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Kho ký ức Tết riêng tư của gia đình.">

        <title>Không gian gia đình | Ký ức Tết</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="app-shell dashboard-shell">
            <header class="site-header" aria-label="Điều hướng chính">
                <a class="brand" href="{{ route('dashboard') }}" aria-label="Ký ức Tết, không gian gia đình">
                    <span class="brand-mark" aria-hidden="true">K</span>
                    <span>Ký ức Tết</span>
                </a>

                <div class="member-summary">
                    <span class="member-name">{{ $user->name }}</span>
                    <span class="role-label">{{ $user->role->label() }}</span>
                </div>
            </header>

            <main class="dashboard-main">
                <section class="dashboard-heading" aria-labelledby="dashboard-title">
                    <div>
                        <p class="eyebrow">Không gian riêng</p>
                        <h1 id="dashboard-title" class="dashboard-title">Những mùa Tết của gia đình.</h1>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="secondary-button" type="submit">Đăng xuất</button>
                    </form>
                </section>

                <section class="dashboard-empty" aria-labelledby="empty-title">
                    <div class="year-stamp">
                        <span>Kho lưu trữ</span>
                        <strong>{{ now()->year }}</strong>
                    </div>

                    <div class="dashboard-empty-copy">
                        <div>
                            <h2 id="empty-title">Album Tết sẽ xuất hiện ở đây</h2>
                            <p>
                                Tài khoản của bạn đã sẵn sàng. Bước tiếp theo là tạo album đầu tiên và đưa những kỷ niệm thân quen vào không gian này.
                            </p>
                        </div>
                    </div>
                </section>
            </main>

            <footer class="site-footer">
                <span>Chỉ thành viên được duyệt mới có thể xem</span>
                <span>Phiên đăng nhập được bảo vệ</span>
            </footer>
        </div>
    </body>
</html>
