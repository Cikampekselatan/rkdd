<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#071827">
        <meta name="description" content="Ruang Komunitas Digital Desa Cikampek Selatan untuk belajar digital, berkarya, dan mengelola program komunitas.">

        <title>@yield('title', config('app.name'))</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <a class="skip-link" href="#main-content">Lewati ke konten utama</a>
        <header class="site-header sticky-top">
            <nav class="navbar navbar-expand-lg" aria-label="Navigasi utama">
                <div class="container py-2">
                    <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('home') }}">
                        <span class="brand-mark" aria-hidden="true">R</span>
                        <span>RKDD Cikampek Selatan</span>
                    </a>

                    @auth
                        <div class="d-flex align-items-center gap-2">
                            <a class="btn btn-skuad-outline d-none d-sm-inline-flex align-items-center gap-2" href="{{ route(auth()->user()->dashboardRouteName()) }}">
                                <i class="bi bi-grid" aria-hidden="true"></i>
                                Dashboard
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="btn btn-skuad-ghost d-inline-flex align-items-center gap-2" type="submit">
                                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                                    Keluar
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="site-header-actions d-flex align-items-center gap-2">
                            <a class="btn btn-skuad-outline d-none d-sm-inline-flex align-items-center gap-2" href="{{ route('login') }}">
                                <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                                Masuk staff
                            </a>
                            <a class="btn btn-skuad-outline d-none d-md-inline-flex align-items-center gap-2" href="{{ route('knowledge.index') }}">
                                <i class="bi bi-book" aria-hidden="true"></i>
                                Ruang Ilmu
                            </a>
                            <a class="btn btn-skuad-outline d-none d-sm-inline-flex align-items-center gap-2" href="{{ route('google.redirect') }}">
                                <i class="bi bi-google" aria-hidden="true"></i>
                                Masuk peserta
                            </a>
                            <a class="btn btn-skuad d-inline-flex align-items-center gap-2" href="{{ route('student.register') }}">
                                <i class="bi bi-google" aria-hidden="true"></i>
                                <span>Gabung</span>
                            </a>
                        </div>
                    @endauth
                </div>
                @guest
                    <div class="container pb-2 d-sm-none">
                        <div class="mobile-public-actions" aria-label="Akses cepat publik">
                            <a href="{{ route('login') }}"><i class="bi bi-person-badge" aria-hidden="true"></i> Staff</a>
                            <a href="{{ route('google.redirect') }}"><i class="bi bi-google" aria-hidden="true"></i> Peserta</a>
                            <a href="{{ route('knowledge.index') }}"><i class="bi bi-book" aria-hidden="true"></i> Ilmu</a>
                        </div>
                    </div>
                @endguest
            </nav>
        </header>

        <main id="main-content">
            @yield('content')
        </main>
    </body>
</html>
