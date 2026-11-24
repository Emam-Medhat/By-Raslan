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
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Cairo','ui-sans-serif','system-ui','sans-serif'] }, colors: { primary:{50:'#f3f6f9',100:'#e4ebf2',200:'#c8d7e5',300:'#9fb9cf',400:'#6d93b3',500:'#3f6f95',600:'#28557b',700:'#1f4463',800:'#17324D',900:'#11263b'}, accent:{50:'#E7F5F3',100:'#d6f4ef',200:'#aee9df',300:'#79d8ca',400:'#42c0ae',500:'#159D91',600:'#117f76',700:'#116660',800:'#12514d',900:'#134341'} } } } }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @vite('resources/css/app.css')
    <style>[x-cloak]{display:none!important}body{font-family:Cairo,sans-serif}</style>
</head>
<body class="min-h-screen bg-[#F4F7FB] text-primary-800 antialiased">
    @yield('content')
</body>
</html>
