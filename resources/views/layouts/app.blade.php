<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Cairo', 'sans-serif'] },
                    colors: { brand: { 600: '#0f766e', 700: '#115e59' } },
                },
            },
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('head')
</head>
<body class="font-sans bg-slate-100 text-slate-800 antialiased">
<div class="min-h-screen md:flex">
    @include('layouts.partials.sidebar')

    <div class="flex-1 md:mr-64 flex flex-col min-w-0">
        @include('layouts.partials.topbar')

        <main class="flex-1 p-4 md:p-6 max-w-full">
            @include('layouts.partials.flash')
            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>