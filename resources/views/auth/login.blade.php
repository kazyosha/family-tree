<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Đăng nhập vào kho ký ức Tết riêng tư của gia đình.">

        <title>Đăng nhập | Ký ức Tết</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="app-shell auth-shell">
            <header class="site-header" aria-label="Điều hướng chính">
                <a class="brand" href="{{ route('home') }}" aria-label="Ký ức Tết, trang chủ">
                    <span class="brand-mark" aria-hidden="true">K</span>
                    <span>Ký ức Tết</span>
                </a>

                <span class="privacy-label">Chỉ dành cho thành viên</span>
            </header>

            <main class="auth-main">
                <section class="auth-intro" aria-labelledby="login-title">
                    <p class="eyebrow">Không gian gia đình</p>
                    <h1 id="login-title" class="auth-title">Mời bạn trở về nhà.</h1>
                    <p class="intro">
                        Đăng nhập để cùng xem lại ảnh, video và âm thanh của những mùa Tết đã qua.
                    </p>
                </section>

                <section class="auth-card" aria-label="Biểu mẫu đăng nhập">
                    <div class="auth-card-heading">
                        <span>Tài khoản thành viên</span>
                        <strong>Riêng tư</strong>
                    </div>

                    <form class="auth-form" method="POST" action="{{ route('login.store') }}">
                        @csrf

                        <div class="form-field">
                            <label for="email">Email</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                autocomplete="email"
                                inputmode="email"
                                required
                                autofocus
                            >
                            @error('email')
                                <p class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-field">
                            <label for="password">Mật khẩu</label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                            >
                            @error('password')
                                <p class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <button class="primary-button" type="submit">Đăng nhập</button>
                    </form>

                    <p class="auth-help">
                        Chưa có tài khoản? Hãy liên hệ chủ sở hữu không gian gia đình.
                    </p>
                </section>
            </main>

            <footer class="site-footer">
                <a class="text-link" href="{{ route('home') }}">Quay lại trang giới thiệu</a>
                <span>Không có đăng ký công khai</span>
            </footer>
        </div>
    </body>
</html>
