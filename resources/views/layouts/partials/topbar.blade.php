@php
    $user = auth()->user();
    $unreadCount = 0;
    $latestNotifications = collect();
    try { $unreadCount = $user ? $user->unreadNotifications()->count() : 0; } catch (\Throwable $e) {}
    try { $latestNotifications = $user ? $user->notifications()->latest()->take(6)->get() : collect(); } catch (\Throwable $e) {}
    $pageTitle = trim($__env->yieldContent('page-title')) ?: 'لوحة التحكم';
@endphp

<header class="app-topbar no-print" x-data="topbar()" dir="rtl">
    <div class="topbar-zone topbar-zone-start">
        <button type="button" class="topbar-icon-btn md:hidden" @click="$dispatch('sidebar-toggle')" aria-label="فتح القائمة">
            <x-icon name="menu" size="20" />
        </button>

        <div class="hidden sm:flex items-center gap-2 min-w-0">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-accent-50 text-accent-600 shrink-0">
                <x-icon name="layout-dashboard" size="18" />
            </span>
            <div class="min-w-0">
                <div class="text-sm font-extrabold text-primary-800 truncate">{{ $pageTitle }}</div>
                <div class="text-[11px] text-slate-400 truncate">BY RASLAN ERP</div>
            </div>
        </div>
    </div>

    <div class="topbar-center">
        <label class="topbar-search" aria-label="بحث">
            <x-icon name="search" size="18" class="text-slate-400" />
            <input type="search" placeholder="ابحث في النظام..." autocomplete="off">
        </label>
    </div>

    <div class="topbar-zone topbar-zone-end">
        <div class="topbar-date hidden lg:flex items-center gap-2 text-slate-500">
            <x-icon name="calendar-days" size="18" class="text-slate-400" />
            <div class="leading-tight">
                <div class="text-[11px] font-semibold text-primary-800">{{ now()->translatedFormat('l') }}</div>
                <div class="text-[11px] text-slate-400">{{ now()->format('Y/m/d') }}</div>
            </div>
        </div>

        <div class="relative" @click.outside="notifOpen = false">
            <button type="button" class="topbar-icon-btn" @click="notifOpen = !notifOpen" :aria-expanded="notifOpen ? 'true' : 'false'" aria-haspopup="true" aria-label="الإشعارات">
                <x-icon name="bell" size="19" />
                @if($unreadCount > 0)
                    <span class="topbar-badge">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                @endif
            </button>
            <div x-show="notifOpen" x-cloak x-transition.origin.top.left class="absolute left-0 top-[calc(100%+10px)] z-40 w-[min(92vw,340px)] rounded-2xl border border-slate-200 bg-white shadow-xl">
                <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100">
                    <div>
                        <div class="text-sm font-bold text-primary-800">الإشعارات</div>
                        <div class="text-[11px] text-slate-400">{{ $unreadCount }} غير مقروء</div>
                    </div>
                    <a href="{{ route('notifications.index') }}" class="text-xs font-semibold text-accent-600 hover:text-accent-700">عرض الكل</a>
                </div>
                <div class="max-h-80 overflow-y-auto">
                    @forelse($latestNotifications as $n)
                        <div class="px-4 py-3 border-b border-slate-50 last:border-0">
                            <div class="text-sm font-semibold text-primary-800">{{ $n->data['title'] ?? class_basename($n->type) }}</div>
                            @if(!empty($n->data['body']))
                                <div class="text-xs text-slate-500 mt-0.5 line-clamp-2">{{ $n->data['body'] }}</div>
                            @endif
                            <div class="text-[11px] text-slate-400 mt-1">{{ optional($n->created_at)->diffForHumans() }}</div>
                        </div>
                    @empty
                        <div class="px-4 py-10 text-center">
                            <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-400"><x-icon name="bell" size="18" /></div>
                            <div class="text-sm text-slate-500">لا توجد إشعارات</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <button type="button" class="topbar-icon-btn" @click="toggleTheme()" :aria-pressed="dark ? 'true' : 'false'" aria-label="تبديل السمة" title="تبديل السمة">
            <span x-show="!dark"><x-icon name="moon" size="18" /></span>
            <span x-show="dark" x-cloak><x-icon name="sun" size="18" /></span>
        </button>

        <div class="relative" @click.outside="menuOpen = false">
            <button type="button" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-2 py-1.5 hover:bg-slate-50 transition" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen ? 'true' : 'false'">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary-800 text-white text-xs font-bold">{{ mb_substr($user?->name ?? '؟', 0, 1) }}</span>
                <span class="hidden md:block text-right min-w-0">
                    <span class="block text-xs font-bold text-primary-800 max-w-[120px] truncate">{{ $user?->name }}</span>
                    <span class="block text-[10px] text-slate-400">{{ $user?->isOwner() ? 'مالك النظام' : 'مستخدم' }}</span>
                </span>
                <x-icon name="chevron-down" size="14" class="text-slate-400 hidden md:block" />
            </button>
            <div x-show="menuOpen" x-cloak x-transition.origin.top.left class="absolute left-0 top-[calc(100%+10px)] z-40 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
                <div class="px-3 py-2 border-b border-slate-100 mb-1">
                    <div class="text-sm font-bold text-primary-800 truncate">{{ $user?->name }}</div>
                    <div class="text-xs text-slate-400 truncate">{{ $user?->email ?: $user?->phone }}</div>
                </div>
                <a href="{{ route('notifications.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
                    <x-icon name="bell" size="16" class="text-slate-400" /> الإشعارات
                </a>
                @if($user && ($user->isOwner() || $user->hasPermission('settings.edit')))
                <a href="{{ route('settings.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
                    <x-icon name="settings" size="16" class="text-slate-400" /> الإعدادات
                </a>
                @endif
                <div class="my-1 border-t border-slate-100"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                        <x-icon name="log-out" size="16" /> تسجيل الخروج
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<script>
function topbar() {
    return {
        notifOpen: false,
        menuOpen: false,
        dark: document.documentElement.getAttribute('data-theme') === 'dark',
        toggleTheme() {
            this.dark = !this.dark;
            document.documentElement.setAttribute('data-theme', this.dark ? 'dark' : 'light');
            try { localStorage.setItem('br.theme', this.dark ? 'dark' : 'light'); } catch (e) {}
        }
    }
}
</script>
