<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Không gian riêng để gia đình lưu giữ ảnh, video và âm thanh mỗi dịp Tết.">

        <title>Ký ức Tết</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="app-shell" x-data="{ detailsOpen: false }">
            <header class="site-header" aria-label="Điều hướng chính">
                <a class="brand" href="{{ route('home') }}" aria-label="Ký ức Tết, trang chủ">
                    <span class="brand-mark" aria-hidden="true">K</span>
                    <span>Ký ức Tết</span>
                </a>

                <span class="privacy-label">Không gian gia đình</span>
            </header>

            <main class="hero">
                <section class="hero-copy" aria-labelledby="page-title">
                    <p class="eyebrow">Ảnh, video và âm thanh theo từng năm</p>
                    <h1 id="page-title">Giữ Tết ở lại cùng gia đình.</h1>
                    <p class="intro">
                        Một nơi riêng tư để các thế hệ cùng xem lại những khoảnh khắc thân quen.
                    </p>
                </section>

                <section class="archive-empty" aria-labelledby="archive-title">
                    <div class="year-stamp" aria-hidden="true">
                        <span>Tết</span>
                        <strong>{{ now()->year }}</strong>
                    </div>

                    <div class="empty-copy">
                        <h2 id="archive-title">Kỷ niệm sẽ được xếp theo từng mùa Tết</h2>
                        <p>Album riêng của gia đình sẽ xuất hiện ở đây sau khi đăng nhập.</p>

                        <button
                            class="details-button"
                            type="button"
                            x-on:click="detailsOpen = ! detailsOpen"
                            x-bind:aria-expanded="detailsOpen"
                            aria-controls="privacy-details"
                        >
                            <span x-text="detailsOpen ? 'Ẩn thông tin' : 'Vì sao cần đăng nhập?'">Vì sao cần đăng nhập?</span>
                        </button>

                        <p id="privacy-details" class="privacy-details" x-show="detailsOpen" x-cloak>
                            Chỉ thành viên được duyệt mới có thể xem. Người có quyền quản lý mới được thêm hoặc chỉnh sửa kỷ niệm.
                        </p>
                    </div>
                </section>
            </main>

            <footer class="site-footer">
                <span>Kho lưu trữ riêng tư của gia đình</span>
                <span>Không cung cấp nút tải video</span>
            </footer>
        </div>
    </body>
</html>
