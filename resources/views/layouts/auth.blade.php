<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#071827">
        <meta name="description" content="Login staff SKUAD Learning Hub.">
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <title>@yield('title', 'Masuk - '.config('app.name'))</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="skuad-auth-body">
        <a class="skip-link" href="#main-content">Lewati ke konten utama</a>
        <main id="main-content">
            @yield('content')
        </main>
    </body>
</html>
