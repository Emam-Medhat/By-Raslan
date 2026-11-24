<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'en' ? 'ltr' : 'rtl' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'BY RASLAN ERP'))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script>
        tailwind = window.tailwind || {};
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Cairo', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    colors: {
                        primary: {
                            50:'#f3f6f9',100:'#e4ebf2',200:'#c8d7e5',300:'#9fb9cf',400:'#6d93b3',
                            500:'#3f6f95',600:'#28557b',700:'#1f4463',800:'#17324D',900:'#11263b',950:'#0c1b2b'
                        },
                        accent: {
                            50:'#E7F5F3',100:'#d6f4ef',200:'#aee9df',300:'#79d8ca',400:'#42c0ae',
                            500:'#159D91',600:'#117f76',700:'#116660',800:'#12514d',900:'#134341'
                        },
                        brand: {
                            50:'#E7F5F3',100:'#d6f4ef',200:'#aee9df',300:'#79d8ca',400:'#42c0ae',
                            500:'#159D91',600:'#159D91',700:'#117f76',800:'#116660',900:'#134341'
                        },
                        navy: {
                            50:'#f3f6f9',100:'#e4ebf2',200:'#c8d7e5',300:'#9fb9cf',400:'#6d93b3',
                            500:'#3f6f95',600:'#28557b',700:'#1f4463',800:'#17324D',900:'#11263b',950:'#102B43'
                        }
                    },
                    boxShadow: {
                        card: '0 1px 2px rgba(23,50,77,.04), 0 8px 24px -18px rgba(23,50,77,.18)',
                    },
                    borderRadius: { xl: '16px' }
                }
            }
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        try { if (localStorage.getItem('br.theme') === 'dark') document.documentElement.setAttribute('data-theme', 'dark'); } catch (e) {}
    </script>
    @vite('resources/css/app.css')
    @stack('head')
</head>
<body class="font-sans antialiased">
<div class="shell" x-data="{ sidebarMobileOpen: false }" @sidebar-toggle.window="sidebarMobileOpen = !sidebarMobileOpen" @sidebar-close.window="sidebarMobileOpen = false">
    @include('layouts.partials.sidebar')
    @include('layouts.partials.topbar')

    <div x-show="sidebarMobileOpen" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-primary-950/45 md:hidden" @click="sidebarMobileOpen = false"></div>

    <main class="main">
        <div class="mx-auto max-w-[1600px] space-y-5">
            @include('layouts.partials.flash')

            @hasSection('page-header')
                @yield('page-header')
            @elseif(View::hasSection('page-title'))
                <div class="page-header">
                    <div>
                        <h1 class="page-title">@yield('page-title')</h1>
                        @hasSection('page-subtitle')
                            <p class="page-subtitle">@yield('page-subtitle')</p>
                        @endif
                    </div>
                    @hasSection('page-actions')
                        <div class="flex flex-wrap items-center gap-2">@yield('page-actions')</div>
                    @endif
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>

@stack('scripts')
</body>
</html>
