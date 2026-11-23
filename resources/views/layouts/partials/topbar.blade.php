@php($user = auth()->user())
<header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 md:px-6">
    <div class="flex items-center gap-3">
        <button class="md:hidden text-slate-500" onclick="document.querySelector('aside').classList.toggle('translate-x-full')">☰</button>
        <span class="font-semibold">@yield('page-title', 'الرئيسية')</span>
    </div>

    <div class="flex items-center gap-3" x-data="{ menu: false }">
        <span class="text-sm text-slate-600">{{ $user?->name }}</span>
        <div class="relative">
            <button @click="menu = !menu" class="w-9 h-9 rounded-full bg-brand-600 text-white font-bold">
                {{ mb_substr($user?->name ?? '؟', 0, 1) }}
            </button>
            <div x-show="menu" @click.outside="menu = false" x-cloak
                 class="absolute left-0 mt-2 w-44 bg-white border border-slate-200 rounded-lg shadow-lg py-1">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="w-full text-right px-4 py-2 text-sm hover:bg-slate-50">تسجيل الخروج</button>
                </form>
            </div>
        </div>
    </div>
</header>